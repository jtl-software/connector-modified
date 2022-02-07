<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductVariationI18n;
use jtl\Connector\Model\ProductVariation;
use jtl\Connector\Model\ProductVariationValue;
use jtl\Connector\Model\ProductVariationValueI18n;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;
use jtl\Connector\Modified\Connector;
use jtl\Connector\Model\Product as ProductModel;

class Product extends AbstractMapper
{
    private static $idCache = [];

    protected $mapperConfig = [
        "table" => "products",
        "query" => "SELECT p.* FROM products p
            LEFT JOIN jtl_connector_link_product l ON p.products_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "products_id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "products_id",
            "ean" => "products_ean",
            "stockLevel" => "ProductStockLevel|setStockLevel",
            "sku" => "products_model",
            "sort" => "products_sort",
            "creationDate" => "products_date_added",
            "availableFrom" => "products_date_available",
            "productWeight" => "products_weight",
            "manufacturerId" => null,
            "manufacturerNumber" => "products_manufacturers_model",
            "unitId" => null,
            "basePriceDivisor" => "products_vpe_value",
            "considerBasePrice" => null,
            "isActive" => "products_status",
            "isTopProduct" => "products_startpage",
            "isMasterProduct" => null,
            "considerStock" => null,
            "considerVariationStock" => null,
            "permitNegativeStock" => null,
            "taxClassId" => 'products_tax_class_id',
            "i18ns" => "ProductI18n|addI18n",
            "categories" => "Product2Category|addCategory",
            "prices" => "ProductPrice|addPrice",
            "specialPrices" => "ProductSpecialPrice|addSpecialPrice",
            "variations" => "ProductVariation|addVariation",
            "invisibilities" => "ProductInvisibility|addInvisibility",
            "attributes" => "ProductAttr|addAttribute",
            "vat" => null,
        ],
        "mapPush" => [
            "products_id" => "id",
            "products_ean" => "ean",
            "products_quantity" => null,
            "products_model" => "sku",
            "products_sort" => "sort",
            "products_date_added" => "creationDate",
            "products_date_available" => "availableFrom",
            "products_weight" => "productWeight",
            "manufacturers_id" => "manufacturerId",
            "products_manufacturers_model" => "manufacturerNumber",
            "products_vpe" => null,
            "products_vpe_value" => "basePriceDivisor",
            "products_vpe_status" => null,
            "products_status" => "isActive",
            "products_startpage" => "isTopProduct",
            "products_tax_class_id" => null,
            "ProductI18n|addI18n" => "i18ns",
            "Product2Category|addCategory" => "categories",
            "ProductPrice|addPrice" => "prices",
            "ProductSpecialPrice|addSpecialPrice" => "specialPrices",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            "ProductVariation|addVariation" => "variations",
            "ProductAttr|addAttribute|true" => "attributes",
            "products_image" => null,
            "products_shippingtime" => null,
            "products_price" => null,
        ],
    ];

    public function pull($data = null, $limit = null): array
    {
        $productResult = parent::pull($data, $limit);

        foreach ($productResult as $parent) {

            /** @var ProductModel $parent */
            if ($parent->getIsMasterProduct()) {
                $masterVariations = [];

                $dbResult = (new \jtl\Connector\Modified\Mapper\ProductVariationValue($this->db, $this->shopConfig, $this->connectorConfig))
                    ->pull(['products_id' => $parent->getId()->getEndpoint()], $limit);

                foreach ($dbResult as $varCombi) {
                    $varCombiAttr = $this->db->query(
                        sprintf(
                            "SELECT * FROM products_attributes WHERE options_values_id = %s AND products_id = %s",
                            $varCombi->getId()->getEndpoint(),
                            $parent->getId()->getEndpoint()
                        )
                    );

                    if (isset($varCombiAttr[0])) {
                        $productVariationI18ns = $this->db->query(
                            sprintf(
                                "SELECT * FROM products_options WHERE products_options_id = %s",
                                $varCombiAttr[0]['options_id']
                            )
                        );

                        $productVariationValueI18ns = $this->db->query(
                            sprintf(
                                "SELECT * FROM products_options_values WHERE products_options_values_id = %s",
                                $varCombiAttr[0]['options_values_id']
                            )
                        );

                        $varCombiProduct = clone $parent;
                        $varCombiProduct
                            ->setId(new Identity(Product::createProductEndpoint($parent->getId()->getEndpoint(), $varCombiAttr[0]['options_values_id'])))
                            ->setMasterProductId($parent->getId())
                            ->setIsMasterProduct(false)
                            ->setConsiderStock(true)
                            ->setIsActive(true)
                            ->setSku($varCombiAttr[0]['attributes_model'] !== '' ? $varCombiAttr[0]['attributes_model'] : sprintf('%s-%s', $parent->getSku(), $varCombiAttr[0]['options_values_id']));

                        $stock = new \jtl\Connector\Model\ProductStockLevel();
                        $stock->setStockLevel($varCombi->getStockLevel());
                        $stock->setProductId($varCombi->getId());
                        $varCombiProduct->setStockLevel($stock);

                        $i18ns = [];
                        foreach ($productVariationValueI18ns as $i18n) {
                            $productI18n = new \jtl\Connector\Model\ProductI18n();
                            $productI18n->setProductId($varCombiProduct->getId());
                            $productI18n->setLanguageISO($this->id2locale($i18n['language_id']));
                            $productI18n->setName($i18n['products_options_values_name']);
                            $i18ns[] = $productI18n;
                        }
                        $varCombiProduct->setI18ns($i18ns);

                        $variationId = $varCombiAttr[0]['options_id'];

                        $variation = new ProductVariation();
                        $variation->setId(new Identity($variationId, null));
                        $variation->setSort($varCombi->getSort());
                        $variation->setType('select');

                        if (!isset($masterVariations[$variationId])) {
                            $masterVariations[$variationId] = $variation;
                        }

                        $variationI18ns = [];
                        foreach ($productVariationI18ns as $variationI18n) {
                            $productVariationI18n = new ProductVariationI18n();
                            $productVariationI18n->setProductVariationId(new Identity($variationId, null));
                            $productVariationI18n->setLanguageISO($this->id2locale($variationI18n['language_id']));
                            $productVariationI18n->setName($variationI18n['products_options_name']);
                            $variationI18ns[] = $productVariationI18n;
                        }
                        $variation->setI18ns($variationI18ns);

                        $value = new ProductVariationValue();
                        $value->setId($varCombi->getId());
                        $value->setExtraWeight($varCombiAttr[0]['weight_prefix'] == "+" ? (float)$varCombiAttr[0]['options_values_weight'] : (float)$varCombiAttr[0]['options_values_weight'] * -1);
                        $value->setSort($varCombi->getSort());
                        $value->setStockLevel($varCombi->getStockLevel());
                        $value->setEan($varCombiAttr[0]['attributes_ean']);

                        $i18ns = [];
                        foreach ($productVariationValueI18ns as $i18n) {
                            $productI18n = new ProductVariationValueI18n();
                            $productI18n->setProductVariationValueId($value->getId());
                            $productI18n->setLanguageISO($this->id2locale($i18n['language_id']));
                            $productI18n->setName($i18n['products_options_values_name']);
                            $i18ns[] = $productI18n;
                        }

                        $masterVariations[$variationId]->addValue($value);

                        $value->setI18ns($i18ns);
                        $variation->setValues([$value]);
                        $variation->setProductId($varCombiProduct->getId());

                        $varCombiProduct->setVariations([$variation]);

                        $productResult[] = $varCombiProduct;
                    }
                }

                $parent->setVariations($masterVariations);
            }
        }

        return $productResult;
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        if (isset(static::$idCache[$model->getMasterProductId()->getHost()]['parentId'])) {
            $model->getMasterProductId()->setEndpoint(static::$idCache[$model->getMasterProductId()->getHost()]['parentId']);
        }

        $masterId = $model->getMasterProductId()->getEndpoint();

        if (count($model->getVariations()) > 0 && !$model->getIsMasterProduct() && $model->getMasterProductId()->getHost() !== 0) {
            $this->addVarCombiAsVariation($model, $masterId);
            Connector::getSessionHelper()->deleteUnusedVariations = true;
            return $model;
        }

        $id = $model->getId()->getEndpoint();

        if (!empty($id)) {
            foreach ($this->getCustomerGroups() as $group) {
                $this->db
                    ->query(
                        'DELETE FROM personal_offers_by_customers_status_'
                        . $group['customers_status_id']
                        . ' WHERE products_id=' . $id
                    );
            }

            $this->db->query('DELETE FROM specials WHERE products_id=' . $id);
        }

        $savedProduct = parent::push($model, $dbObj);

        static::$idCache[$model->getId()->getHost()]['parentId'] = $savedProduct->getId()->getEndpoint();

        return $savedProduct;
    }

    public function delete(DataModel $data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            try {
                if (Product::isVariationChild($id)) {
                    $this->db->query('DELETE FROM products_attributes WHERE options_values_id=' . Product::extractOptionValueId($id));
                    $this->db->query('DELETE FROM products_options_values WHERE products_options_values_id=' . Product::extractOptionValueId($id));
                    $this->db->query('DELETE FROM products_options_values_to_products_options WHERE products_options_values_id=' . Product::extractOptionValueId($id));
                    $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id=' . $id);
                } else {
                    $this->db->query('DELETE FROM products WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_to_categories WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_description WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_images WHERE products_id=' . $id);
                    $result = $this->db->query('SELECT options_values_id FROM products_attributes WHERE products_id=' . $id);
                    foreach ($result as $item) {
                        if (isset($item['options_values_id'])) {
                            $this->db->query('DELETE FROM products_options_values WHERE products_options_values_id=' . $item['options_values_id']);
                            $this->db->query('DELETE FROM products_options_values_to_products_options WHERE products_options_values_id=' . $item['options_values_id']);
                            $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id=' . Product::createProductEndpoint($id, $item['options_values_id']));
                        }
                    }
                    $this->db->query('DELETE FROM products_attributes WHERE products_id=' . $id);
                    $this->db->query('DELETE FROM products_xsell WHERE products_id=' . $id . ' OR xsell_id=' . $id);
                    $this->db->query('DELETE FROM specials WHERE products_id=' . $id);

                    foreach ($this->getCustomerGroups() as $group) {
                        $this->db->query('DELETE FROM personal_offers_by_customers_status_' . $group['customers_status_id'] . ' WHERE products_id=' . $id);
                    }

                    $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id="' . $id . '"');
                }
            } catch (\Exception $e) {
            }
        }

        Connector::getSessionHelper()->deleteUnusedVariations = true;
        return $data;
    }

    public function statistic(): int
    {
        $sql = 'SELECT count(p.products_id) as count 
                FROM products p 
                LEFT JOIN jtl_connector_link_product l ON p.products_id = l.endpoint_id 
                WHERE l.host_id IS NULL LIMIT 1';

        $objs = $this->db->query($sql, ["return" => "object"]);

        if (isset($objs[0])) {
            $objs = (int)$objs[0]->count;
        } else {
            Logger::write('No objects were found');
        }

        $sql = 'SELECT count(a.products_attributes_id) as count 
                FROM products p
                LEFT JOIN products_attributes a ON p.products_id = a.products_id
                WHERE CONCAT(a.products_id, \'_\', a.options_values_id) NOT IN (SELECT l.endpoint_id FROM jtl_connector_link_product l WHERE LOCATE(\'_\', l.endpoint_id) > 0)';

        $combis = $this->db->query($sql, ["return" => "object"]);

        if (isset($combis[0])) {
            $objs += (int)$combis[0]->count;
        } else {
            Logger::write('No varCobis were found');
        }

        return $objs;
    }

    protected function considerBasePrice($data)
    {
        return $data['products_vpe_status'] == 1 ? true : false;
    }

    protected function products_vpe($data)
    {
        $name = $data->getBasePriceUnitCode();
        if ($data->getBasePriceQuantity() !== 1.) {
            $name = sprintf("%s %s", $data->getBasePriceQuantity(), $data->getBasePriceUnitCode());
        }

        if (!empty($name)) {
            foreach ($data->getI18ns() as $i18n) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT products_vpe_id FROM products_vpe WHERE language_id=' . $language_id . ' && products_vpe_name="' . $name . '"');
                    if (count($sql) > 0) {
                        return $sql[0]['products_vpe_id'];
                    } else {
                        $nextId = $this->db->query('SELECT max(products_vpe_id) + 1 AS nextID FROM products_vpe');
                        $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];

                        foreach ($data->getI18ns() as $i18n) {
                            $status = new \stdClass();
                            $status->products_vpe_id = $id;
                            $status->language_id = $this->locale2id($i18n->getLanguageISO());
                            $status->products_vpe_name = $name;

                            $this->db->deleteInsertRow(
                                $status,
                                'products_vpe',
                                ['products_vpe_id', 'language_id'],
                                [$status->product_vpe_id, $status->language_id]
                            );
                        }

                        return $id;
                    }
                }
            }
        }

        return 0;
    }

    protected function products_shippingtime($data)
    {
        foreach ($data->getI18ns() as $i18n) {
            $name = $i18n->getDeliveryStatus();

            if (!empty($name)) {
                $language_id = $this->locale2id($i18n->getLanguageISO());
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id=' . $language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT shipping_status_id FROM shipping_status WHERE language_id=' . $language_id . ' && shipping_status_name="' . $name . '"');
                    if (count($sql) > 0) {
                        return $sql[0]['shipping_status_id'];
                    } else {
                        $nextId = $this->db->query('SELECT max(shipping_status_id) + 1 AS nextID FROM shipping_status');
                        $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];

                        foreach ($data->getI18ns() as $i18n) {
                            $status = new \stdClass();
                            $status->shipping_status_id = $id;
                            $status->language_id = $this->locale2id($i18n->getLanguageISO());
                            $status->shipping_status_name = $i18n->getDeliveryStatus();

                            $this->db->deleteInsertRow(
                                $status,
                                'shipping_status',
                                ['shipping_status_id', 'langauge_id'],
                                [$status->shipping_status_id, $status->language_id]
                            );
                        }

                        return $id;
                    }
                }
            }
        }

        return $this->shopConfig['settings']['DEFAULT_SHIPPING_STATUS_ID'];
    }

    protected function products_vpe_status($data)
    {
        return $data->getConsiderBasePrice() == true ? 1 : 0;
    }

    protected function products_image($data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id)) {
            $img = $this->db->query('SELECT products_image FROM products WHERE products_id =' . $id);
            $img = $img[0]['products_image'];

            if (isset($img)) {
                return $img;
            }
        }

        return '';
    }

    protected function products_price($data)
    {
        return 999999;
    }

    protected function manufacturerId($data)
    {
        return $this->replaceZero((string)$data['manufacturers_id']);
    }

    protected function unitId($data)
    {
        return $this->replaceZero($data['products_vpe']);
    }

    protected function considerStock($data)
    {
        return true;
    }

    protected function considerVariationStock($data)
    {
        $check = $this->db->query('SELECT products_id FROM products_attributes WHERE products_id=' . $data['products_id']);

        return count($check) > 0 ? true : false;
    }

    protected function permitNegativeStock($data)
    {
        return $this->shopConfig['settings']['STOCK_ALLOW_CHECKOUT'];
    }

    protected function vat($data)
    {
        $sql = $this->db->query(sprintf('SELECT r.tax_rate FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id = r.tax_zone_id WHERE z.zone_country_id = %s AND r.tax_class_id = %s', $this->shopConfig['settings']['STORE_COUNTRY'], $data['products_tax_class_id']));
        if (empty($sql)) {
            $sql = $this->db->query(sprintf('SELECT tax_rate FROM tax_rates WHERE tax_rates_id = %s', $this->connectorConfig->tax_rate));
        }

        return floatval($sql[0]['tax_rate']);
    }

    /**
     * @param ProductModel $product
     * @param ProductModel|null $model
     * @return mixed|string
     */
    protected function products_tax_class_id(ProductModel $product, ProductModel $model = null)
    {
        if (!is_null($product->getTaxClassId()) && !empty($product->getTaxClassId()->getEndpoint())) {
            $taxClassId = $product->getTaxClassId()->getEndpoint();
        } else {
            $taxClasses = $this->db->query(sprintf('SELECT r.tax_class_id FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id = r.tax_zone_id WHERE z.zone_country_id = %s AND r.tax_rate = %s', $this->shopConfig['settings']['STORE_COUNTRY'], $product->getVat()));
            if (empty($taxClasses)) {
                $taxClasses = $this->db->query(sprintf('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id = %s', $this->connectorConfig->tax_rate));
            }

            $taxClassId = $taxClasses[0]['tax_class_id'] ?? '1';
            if (count($product->getTaxRates()) > 0 && !is_null($product->getTaxClassId())) {
                $taxClassId = $this->findTaxClassId(...$product->getTaxRates()) ?? $taxClassId;
                //$model->getTaxClassId()->setEndpoint($taxClassId)->setHost($product->getTaxClassId()->getHost());
            }
        }

        return $taxClassId;
    }

    protected function products_quantity($data)
    {
        return round($data->getStockLevel()->getStockLevel());
    }

    protected function isMasterProduct($data)
    {
        $childCount = $this->db->query("SELECT COUNT(*) AS cnt FROM products_attributes WHERE products_id = " . $data['products_id']);
        return (int)$childCount[0]['cnt'] > 0;
    }

    /**
     * @param $languageIso
     * @param ProductVariation $variation
     * @param ProductVariation ...$moreVariations
     * @return string
     */
    protected function createProductsOptionsName(string $languageIso, ProductVariation $variation, ProductVariation ...$moreVariations) : string
    {
        $variations = array_merge([$variation], $moreVariations);

        $nameParts = [];
        /** @var ProductVariation $variation */
        foreach ($variations as $variation) {
            foreach ($variation->getI18ns() as $i18n) {
                if ($languageIso === $i18n->getLanguageISO()) {
                    $nameParts[] = $i18n->getName();
                }
            }
        }

        $productsOptionName = 'Variation';
        if (!empty($nameParts)) {
            $productsOptionName = implode(' / ', $nameParts);
        }

        return $productsOptionName;
    }

    /**
     * @param ProductModel $data
     * @param string $masterId
     */
    protected function addVarCombiAsVariation(ProductModel $data, string $masterId) : void
    {
        $mainLanguageIso = $this->fullLocale($this->shopConfig['settings']['DEFAULT_LANGUAGE']);
        $mainLanguageProductsOptionsName = $this->createProductsOptionsName($mainLanguageIso, ...$data->getVariations());
        $productsOptionsIdResult = $this->db->query(sprintf("SELECT IFNULL((SELECT products_options_id FROM products_options WHERE language_id = %s AND products_options_name = '%s'), (SELECT MAX(products_options_id) + 1 FROM products_options)) as products_options_id", parent::locale2id($mainLanguageIso), $mainLanguageProductsOptionsName));
        $productsOptionsId = $productsOptionsIdResult[0]['products_options_id'] ?? 1;

        foreach ($data->getVariations()[0]->getValues()[0]->getI18ns() as $i18nId => $variationI18n) {
            $languageIso = $variationI18n->getLanguageISO();
            $languageId = parent::locale2id($languageIso);

            $productsOptionsName = $this->createProductsOptionsName($languageIso, ...$data->getVariations());

            $this->db->query(
                sprintf(
                    "INSERT INTO products_options (products_options_id, language_id, products_options_name, products_options_sortorder) VALUES (%s, %s, '%s', 0) ON DUPLICATE KEY UPDATE products_options_name = '%s'",
                    $productsOptionsId,
                    $languageId,
                    $productsOptionsName,
                    $productsOptionsName
                )
            );

            if (isset(static::$idCache[$data->getId()->getHost()]['valuesId'])) {
                $optionsValuesId = static::$idCache[$data->getId()->getHost()]['valuesId'];
            } else {
                $optionsValuesId = (int)$this->db->query(
                    "SELECT products_options_values_id FROM products_options_values ORDER BY products_options_values_id DESC LIMIT 0,1"
                )[0]["products_options_values_id"];
                $optionsValuesId = $optionsValuesId >= 0 ? ($optionsValuesId + 1) : 1;
            }

            $productsOptionsValuesName = [];

            $i = 0;
            foreach ($data->getVariations() as $var) {
                if (isset($var->getValues()[0]->getI18ns()[$i18nId])) {
                    $productsOptionsValuesName[] = $var->getValues()[0]->getI18ns()[$i18nId]->getName();
                }

                if ($i < count($data->getVariations()) - 1 && count($data->getVariations()) > 1) {
                    $productsOptionsValuesName[] = " | ";
                }
                $i++;
            }

            $result = $this->db->query(
                sprintf(
                    "SELECT * FROM jtl_connector_link_product WHERE host_id = %s",
                    $data->getId()->getHost()
                )
            );

            if (count($result) > 0) {
                $optionsValuesId = Product::extractOptionValueId($result[0]['endpoint_id']);
            } else {
                $this->db->query(
                    sprintf(
                        "INSERT INTO jtl_connector_link_product (endpoint_id, host_id) VALUES ('%s', %s)",
                        Product::createProductEndpoint($masterId, $optionsValuesId),
                        $data->getId()->getHost()
                    )
                );
                static::$idCache[$data->getId()->getHost()]['valuesId'] = $optionsValuesId;
            }

            $variationValue = new \stdClass();
            $variationValue->products_options_values_name = implode('', $productsOptionsValuesName);
            $variationValue->products_options_values_id = $optionsValuesId;
            $variationValue->language_id = $languageId;

            if (version_compare($this->shopConfig['db']['version'], '2.0.4', '>=')) {
                $variationValue->products_options_values_sortorder = 0;
            }

            $this->db->deleteInsertRow(
                $variationValue,
                'products_options_values',
                ['products_options_values_id', 'language_id'],
                [$optionsValuesId, $languageId]
            );

            $price = $this->db->query("SELECT products_price FROM products WHERE products_id = " . $masterId);
            $jtlPriceItem = self::getDefaultPriceItem(...$data->getPrices());
            if (is_null($jtlPriceItem) || !isset($price[0]['products_price'])) {
                throw new \RuntimeException('The VarCombi price has not been set');
            }

            $price = $jtlPriceItem->getNetPrice() - (float)$price[0]['products_price'];
            if ($price >= 0) {
                $pricePrefix = "+";
            } else {
                $pricePrefix = "-";
                $price = $price * -1;
            }

            $result2 = $this->db->query(
                sprintf(
                    "SELECT * FROM products_attributes WHERE options_values_id = %s AND products_id = %s",
                    $optionsValuesId,
                    $masterId
                )
            );

            $sku = $data->getSku();
            $stock = $data->getStockLevel()->getStockLevel();
            $weight = $data->getProductWeight();
            $weightPrefix = "+";
            $sort = $data->getSort();
            $ean = $data->getEan();
            $vpe = 0;

            if (count($result2) > 0) {
                $this->db->query(
                    sprintf(
                        "UPDATE products_attributes SET options_id = %s, options_values_price = %s, price_prefix = '%s', attributes_model = '%s', attributes_stock = %s, options_values_weight = %s, weight_prefix = '%s', sortorder = %s, attributes_ean = '%s', attributes_vpe_id = %s, attributes_vpe_value = %s WHERE options_values_id = %s AND products_id = %s",
                        $productsOptionsId,
                        (double)$price,
                        $pricePrefix,
                        $sku,
                        $stock,
                        $weight,
                        $weightPrefix,
                        $sort,
                        empty($ean) ? '' : $ean,
                        $vpe,
                        $vpe,
                        $optionsValuesId,
                        $masterId
                    )
                );
            } else {
                $this->db->query(
                    sprintf(
                        "INSERT IGNORE INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix, attributes_model, attributes_stock, options_values_weight, weight_prefix, sortorder, attributes_ean, attributes_vpe_id, attributes_vpe_value) VALUES (%s, %s, %s, %s, '%s', '%s', %s, %s, '%s', %s, '%s', %s, %s)",
                        $masterId,
                        $productsOptionsId,
                        $optionsValuesId,
                        (double)$price,
                        $pricePrefix,
                        $sku,
                        $stock,
                        $weight,
                        $weightPrefix,
                        $sort,
                        empty($ean) ? '' : $ean,
                        $vpe,
                        $vpe
                    )
                );
            }

            $pivotTableResult = $this->db->query(
                sprintf(
                    "SELECT * FROM products_options_values_to_products_options WHERE products_options_id = %s AND products_options_values_id = %s",
                    $productsOptionsId,
                    $optionsValuesId
                )
            );

            if (count($pivotTableResult) == 0) {
                $this->db->query(
                    sprintf(
                        "INSERT IGNORE INTO products_options_values_to_products_options (products_options_id, products_options_values_id) VALUES (%s, %s)",
                        $productsOptionsId,
                        $optionsValuesId
                    )
                );
            }
        }
    }

    /**
     * @param ProductPriceModel ...$prices
     * @return ProductPriceItemModel
     */
    public static function getDefaultPriceItem(ProductPriceModel ...$prices): ?ProductPriceItemModel
    {
        foreach ($prices as $price) {
            if ($price->getCustomerGroupId()->getHost() === 0 && $price->getCustomerId()->getHost() === 0) {
                foreach ($price->getItems() as $priceItem) {
                    if ($priceItem->getQuantity() === 0) {
                        return $priceItem;
                    }
                }
            }
        }

        return null;
    }

    public static function createProductEndpoint($parentId, $optionsValuesId)
    {
        return $parentId . "_" . $optionsValuesId;
    }

    public static function extractParentId($endpoint)
    {
        if (self::isVariationChild($endpoint)) {
            $data = explode('_', (string)$endpoint);
            return $data[0];
        } else {
            return $endpoint;
        }
    }

    public static function extractOptionValueId($endpoint)
    {
        if (self::isVariationChild($endpoint)) {
            $data = explode('_', (string)$endpoint);
            return $data[1];
        }

        throw new \Error($endpoint . ' is not a Valid VarKombi endpoint');
    }

    public static function isVariationChild($endpoint)
    {
        $data = explode('_', (string)$endpoint);
        return isset($data[1]);
    }

    /**
     * @param \jtl\Connector\Model\TaxRate ...$taxRates
     * @return string|null
     */
    protected function findTaxClassId(\jtl\Connector\Model\TaxRate ...$taxRates): ?string
    {
        $conditions = [];
        foreach ($taxRates as $taxRate) {
            $conditions[] = sprintf("(c.countries_iso_code_2 = '%s' AND tr.tax_rate = '%s')", $taxRate->getCountryIso(), number_format($taxRate->getRate(), 4));
        }

        $taxClasses = $this->db->query(sprintf('SELECT tax_class_id, COUNT(tax_class_id) as hits
                FROM tax_rates tr
                LEFT JOIN zones_to_geo_zones ztgz ON tr.tax_zone_id = ztgz.geo_zone_id
                LEFT JOIN countries c ON ztgz.zone_country_id = c.countries_id
                WHERE %s
                GROUP BY tax_class_id
                ORDER BY hits DESC', join(' OR ', $conditions)));

        return $taxClasses[0]['tax_class_id'] ?? null;
    }
}
