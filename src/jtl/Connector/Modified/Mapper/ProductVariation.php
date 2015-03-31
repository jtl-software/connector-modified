<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;
use \jtl\Connector\Linker\ChecksumLinker;

class ProductVariation extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] GROUP BY options_id',
        "where" => "options_id",
        "getMethod" => "getVariations",
        "mapPull" => array(
            "id" => "options_id",
            "productId" => "products_id",
            "sort" => "sort_order",
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue"
        )
    );

    public function push($parent, $dbObj)
    {
        if (count($parent->getVariations()) > 0) {
            $checksum = ChecksumLinker::find($parent, 1);

            if ($checksum === null || $checksum->hasChanged() === true) {
                // clear existing product variations
                $this->db->query('DELETE FROM products_attributes WHERE products_id='.$parent->getId()->getEndpoint());

                foreach ($parent->getVariations() as $variation) {
                    // get variation name in default language
                    foreach ($variation->getI18ns() as $i18n) {
                        if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                            $varName = $i18n->getName();
                        }
                    }

                    // try to find existing variation id
                    $variationIdQuery = $this->db->query('SELECT products_options_id FROM products_options WHERE products_options_name="'.$varName.'"');

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

                        $this->db->deleteInsertRow($varObj, 'products_options', array('products_options_id', 'language_id'), array($variationId, $varObj->language_id));
                    }

                    // VariationValues
                    foreach ($variation->getValues() as $value) {
                        // get value name in default language
                        foreach ($value->getI18ns() as $i18n) {
                            if ($i18n->getLanguageISO() == $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE'])) {
                                $valueName = $i18n->getName();
                            }
                        }

                        // try to find existing value id
                        $valueIdQuery = $this->db->query('SELECT v2.products_options_values_id FROM products_options_values_to_products_options v1 LEFT JOIN products_options_values v2 ON v1.products_options_values_id=v2.products_options_values_id WHERE v1.products_options_id='.$variationId.' && v2.products_options_values_name="'.$valueName.'"');

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

                            $this->db->deleteInsertRow($valueObj, 'products_options_values', array('products_options_values_id', 'language_id'), array($valueId, $valueObj->language_id));
                        }

                        // insert/update values to variation mapping
                        $val2varObj = new \stdClass();
                        $val2varObj->products_options_id = $variationId;
                        $val2varObj->products_options_values_id = $valueId;

                        $this->db->deleteInsertRow($val2varObj, 'products_options_values_to_products_options', array('products_options_id', 'products_options_values_id'), array($variationId, $valueId));

                        // insert/update product variation
                        $pVarObj = new \stdClass();
                        $pVarObj->products_id = $parent->getId()->getEndpoint();
                        $pVarObj->options_id = $variationId;
                        $pVarObj->options_values_id = $valueId;
                        $pVarObj->attributes_stock = $value->getStockLevel();
                        $pVarObj->options_values_weight = abs($value->getExtraWeight());
                        $pVarObj->weight_prefix = $value->getExtraWeight() < 0 ? '-' : '+';
                        $pVarObj->sortorder = $value->getSort();
                        $pVarObj->attributes_ean = $value->getSku();

                        // get product variation price for default customer group
                        foreach ($value->getExtraCharges() as $extraCharge) {
                            if ($extraCharge->getCustomerGroupId()->getEndpoint() == $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID']) {
                                $pVarObj->price_prefix = $extraCharge->getExtraChargeNet() < 0 ? '-' : '+';
                                $pVarObj->options_values_price = abs($extraCharge->getExtraChargeNet());
                            }
                        }

                        $this->db->insertRow($pVarObj, 'products_attributes');
                    }
                }
            }
        }

        $this->clearUnusedVariations();

        return $parent->getVariations();
    }

    private function clearUnusedVariations()
    {
        $this->db->query('
            DELETE FROM products_options_values
            WHERE products_options_values_id IN (
                SELECT * FROM (
                    SELECT v.products_options_values_id
                    FROM products_options_values v
                    LEFT JOIN products_attributes a ON v.products_options_values_id = a.options_values_id
                    WHERE a.products_attributes_id IS NULL
                    GROUP BY v.products_options_values_id
                ) relations
            )
        ');

        $this->db->query('
            DELETE FROM products_options
            WHERE products_options_id IN (
                SELECT * FROM (
                    SELECT o.products_options_id
                    FROM products_options o
                    LEFT JOIN products_attributes a ON o.products_options_id = a.options_id
                    WHERE a.products_attributes_id IS NULL
                    GROUP BY o.products_options_id
                ) relations
            )
        ');
    }
}
