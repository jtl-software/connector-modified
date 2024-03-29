<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Formatter\ExceptionFormatter;
use jtl\Connector\Linker\ChecksumLinker;
use jtl\Connector\Modified\Connector;

class ProductVariation extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_options",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] GROUP BY options_id',
        "where" => "options_id",
        "getMethod" => "getVariationCombinations",
        "mapPull" => [
            "id" => "options_id",
            "productId" => "products_id",
            "sort" => "sort_order",
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue"
        ]
    ];

    public function push($parent, $dbObj = null)
    {
        if (count($parent->getVariations()) > 0 && !$parent->getIsMasterProduct() && $parent->getMasterProductId()->getHost() === 0) {
            $checksum = ChecksumLinker::find($parent, 1);

            if ($checksum === null || $checksum->hasChanged() === true) {
                $totalStock = 0;

                // clear existing product variations
                $this->db->query('DELETE FROM products_attributes WHERE products_id=' . $parent->getId()->getEndpoint());

                /** @var \jtl\Connector\Model\ProductVariation $variation */
                foreach ($parent->getVariations() as $variation) {
                    // get variation name in default language
                    foreach ($variation->getI18ns() as $i18n) {
                        if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                            $varName = $i18n->getName();
                        }
                    }

                    // try to find existing variation id
                    $variationIdQuery = $this->db->query('SELECT products_options_id FROM products_options WHERE products_options_name="' . $varName . '"');

                    // use existing id or generate next available one
                    if (count($variationIdQuery) > 0) {
                        $variationId = $variationIdQuery[0]['products_options_id'];
                    } else {
                        $nextId = $this->db->query('SELECT max(products_options_id) + 1 AS nextID FROM products_options');
                        $variationId = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
                    }

                    // insert/update variation
                    foreach ($variation->getI18ns() as $i18n) {
                        $varObj = new \stdClass();
                        $varObj->products_options_id = $variationId;
                        $varObj->language_id = $this->locale2id($i18n->getLanguageISO());
                        $varObj->products_options_name = $i18n->getName();
                        $varObj->products_options_sortorder = $variation->getSort();

                        $this->db->deleteInsertRow($varObj, 'products_options', ['products_options_id', 'language_id'], [$variationId, $varObj->language_id]);
                    }

                    // VariationValues
                    foreach ($variation->getValues() as $value) {
                        // get value name in default language
                        foreach ($value->getI18ns() as $i18n) {
                            if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                                $valueName = $i18n->getName();
                                break;
                            }
                        }

                        // try to find existing value id
                        $valueIdQuery = $this->db->query('SELECT v2.products_options_values_id FROM products_options_values_to_products_options v1 LEFT JOIN products_options_values v2 ON v1.products_options_values_id=v2.products_options_values_id WHERE v1.products_options_id=' . $variationId . ' && v2.products_options_values_name="' . $valueName . '"');

                        // use existing id or generate next available one
                        if (count($valueIdQuery) > 0) {
                            $valueId = $valueIdQuery[0]['products_options_values_id'];
                        } else {
                            $nextId = $this->db->query('SELECT max(products_options_values_id) + 1 AS nextID FROM products_options_values');
                            $valueId = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
                        }

                        // insert/update values
                        foreach ($value->getI18ns() as $i18n) {
                            $valueObj = new \stdClass();
                            $valueObj->products_options_values_id = $valueId;
                            $valueObj->language_id = $this->locale2id($i18n->getLanguageISO());
                            $valueObj->products_options_values_name = $i18n->getName();
                            $valueObj->products_options_values_sortorder = $value->getSort();

                            $this->db->deleteInsertRow($valueObj, 'products_options_values', ['products_options_values_id', 'language_id'], [$valueId, $valueObj->language_id]);
                        }

                        // insert/update values to variation mapping
                        $val2varObj = new \stdClass();
                        $val2varObj->products_options_id = $variationId;
                        $val2varObj->products_options_values_id = $valueId;

                        $this->db->deleteInsertRow(
                            $val2varObj,
                            'products_options_values_to_products_options',
                            ['products_options_id', 'products_options_values_id'],
                            [$variationId, $valueId]
                        );

                        // insert/update product variation
                        $pVarObj = new \stdClass();
                        $pVarObj->products_id = $parent->getId()->getEndpoint();
                        $pVarObj->options_id = $variationId;
                        $pVarObj->options_values_id = $valueId;
                        $pVarObj->attributes_stock = round($value->getStockLevel());
                        $pVarObj->options_values_weight = abs($value->getExtraWeight());
                        $pVarObj->weight_prefix = $value->getExtraWeight() < 0 ? '-' : '+';
                        $pVarObj->sortorder = $value->getSort();
                        $pVarObj->attributes_model = $value->getSku();
                        $pVarObj->attributes_ean = $value->getEan();
                        $pVarObj->attributes_vpe_id = 0;
                        $pVarObj->attributes_vpe_value = 0;

                        $totalStock += $pVarObj->attributes_stock;

                        // get product variation price for default customer group
                        foreach ($value->getExtraCharges() as $extraCharge) {
                            if ($extraCharge->getCustomerGroupId()->getHost() === 0) {
                                $pVarObj->price_prefix = $extraCharge->getExtraChargeNet() < 0 ? '-' : '+';
                                $pVarObj->options_values_price = abs($extraCharge->getExtraChargeNet());

                                $this->db->insertRow($pVarObj, 'products_attributes');
                                break;
                            }
                        }
                    }
                }

                if ($parent->getStockLevel()->getStockLevel() == 0) {
                    $this->db->query('UPDATE products SET products_quantity=' . $totalStock . ' WHERE products_id=' . $parent->getId()->getEndpoint());
                }
            }

            Connector::getSessionHelper()->deleteUnusedVariations = true;
            return $parent->getVariations();
        }
    }
}
