<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductVarCombination;

class Product extends BaseMapper
{
    private static $idCache = [];
    
    protected $mapperConfig = [
        "table"    => "products",
        "query"    => "SELECT p.* FROM products p
            LEFT JOIN jtl_connector_link_product l ON p.products_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where"    => "products_id",
        "identity" => "getId",
        "mapPull"  => [
            "id"                     => "products_id",
            "ean"                    => "products_ean",
            "stockLevel"             => "ProductStockLevel|setStockLevel",
            "sku"                    => "products_model",
            "sort"                   => "products_sort",
            "creationDate"           => "products_date_added",
            "availableFrom"          => "products_date_available",
            "productWeight"          => "products_weight",
            "manufacturerId"         => null,
            "manufacturerNumber"     => "products_manufacturers_model",
            "unitId"                 => null,
            "basePriceDivisor"       => "products_vpe_value",
            "considerBasePrice"      => null,
            "isActive"               => "products_status",
            "isTopProduct"           => "products_startpage",
            "isMasterProduct"        => null,
            "considerStock"          => null,
            "considerVariationStock" => null,
            "permitNegativeStock"    => null,
            "i18ns"                  => "ProductI18n|addI18n",
            "categories"             => "Product2Category|addCategory",
            "prices"                 => "ProductPrice|addPrice",
            "specialPrices"          => "ProductSpecialPrice|addSpecialPrice",
            "variations"             => "ProductVariation|addVariation",
            "invisibilities"         => "ProductInvisibility|addInvisibility",
            "attributes"             => "ProductAttr|addAttribute",
            "vat"                    => null,
        ],
        "mapPush"  => [
            "products_id"                              => "id",
            "products_ean"                             => "ean",
            "products_quantity"                        => null,
            "products_model"                           => "sku",
            "products_sort"                            => "sort",
            "products_date_added"                      => "creationDate",
            "products_date_available"                  => "availableFrom",
            "products_weight"                          => "productWeight",
            "manufacturers_id"                         => "manufacturerId",
            "products_manufacturers_model"             => "manufacturerNumber",
            "products_vpe"                             => null,
            "products_vpe_value"                       => "measurementQuantity",
            "products_vpe_status"                      => null,
            "products_status"                          => "isActive",
            "products_startpage"                       => "isTopProduct",
            "products_tax_class_id"                    => null,
            "ProductI18n|addI18n"                      => "i18ns",
            "Product2Category|addCategory"             => "categories",
            "ProductPrice|addPrice"                    => "prices",
            "ProductSpecialPrice|addSpecialPrice"      => "specialPrices",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            "ProductAttr|addAttribute|true"            => "attributes",
            "products_image"                           => null,
            "products_shippingtime"                    => null,
            "products_price"                           => null,
        ],
    ];
    
    public function pull($data = null, $limit = null)
    {
        $productResult = parent::pull($data, $limit);
        
        foreach ($productResult as $parent) {
            
            /** @var \jtl\Connector\Model\Product $parent */
            if ($parent->getIsMasterProduct()) {
                $dbResult = (new ProductVariationValue())->pull(['products_id' => $parent->getId()->getEndpoint()], $limit);
                foreach ($dbResult as $varCombi) {
    
                    $varCombiAttr = $this->db->query(
                        sprintf("SELECT * FROM products_attributes WHERE options_values_id = %s",
                            $varCombi->getId()->getEndpoint()
                        )
                    );
    
                    $productVariationI18ns = $this->db->query(
                        sprintf("SELECT * FROM products_options WHERE products_options_id = %s",
                            $varCombiAttr[0]['options_id']
                        )
                    );
    
                    $productVariationValueI18ns = $this->db->query(
                        sprintf("SELECT * FROM products_options_values WHERE products_options_values_id = %s",
                            $varCombiAttr[0]['options_values_id']
                        )
                    );
                    
                    if (isset($varCombiAttr[0])) {
                        
                        $varCombiProduct = clone $parent;
                        $varCombiProduct->setId(new Identity($varCombiAttr[0]['products_attributes_id'], $varCombiAttr[0]['host_id']));
                        $varCombiProduct->setMasterProductId($parent->getId());
                        $varCombiProduct->setIsMasterProduct(false);
                        $varCombiProduct->setConsiderStock(true);
                        $varCombiProduct->setIsActive(true);
                        $varCombiProduct->setSku($varCombiAttr[0]['attributes_model']);
                        
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
                        
                        $variation = new \jtl\Connector\Model\ProductVariation();
                        $variation->setId(new Identity($varCombiAttr[0]['products_options_id'], null));
                        $variation->setSort($varCombi->getSort());
                        $variation->setType("select");
    
                        $variationI18ns = [];
                        foreach ($productVariationI18ns as $variationI18n) {
                            $productVariationI18n = new \jtl\Connector\Model\ProductVariationI18n();
                            $productVariationI18n->setProductVariationId(new Identity($varCombiAttr[0]['products_options_id'], null));
                            $productVariationI18n->setLanguageISO($this->id2locale($variationI18n['language_id']));
                            $productVariationI18n->setName($variationI18n['products_options_name']);
                            $variationI18ns[] = $productVariationI18n;
                        }
                        $variation->setI18ns($variationI18ns);
                        
                        $value = new \jtl\Connector\Model\ProductVariationValue();
                        $value->setExtraWeight($varCombiAttr[0]['weight_prefix'] == "+" ? (float)$varCombiAttr[0]['options_values_weight'] : (float)$varCombiAttr[0]['options_values_weight'] * -1);
                        $value->setSort($varCombi->getSort());
                        $value->setStockLevel($varCombi->getStockLevel());
                        $value->setEan($varCombiAttr[0]['attributes_ean']);
    
                        $i18ns = [];
                        foreach ($productVariationValueI18ns as $i18n) {
                            $productI18n = new \jtl\Connector\Model\ProductVariationValueI18n();
                            $productI18n->setProductVariationValueId(new Identity($varCombiAttr[0]['products_options_values_id'], null));
                            $productI18n->setLanguageISO($this->id2locale($i18n['language_id']));
                            $productI18n->setName($i18n['products_options_values_name']);
                            $i18ns[] = $productI18n;
                        }
                        $value->setI18ns($i18ns);
                        $variation->setValues([$value]);
                        $variation->setProductId($varCombiProduct->getId());
                        
                        $varCombiProduct->setVariations([$variation]);
                        
                        $productResult[] = $varCombiProduct;
                    }
                }
            }
        }
        
        return $productResult;
    }
    
    public function push($data, $dbObj = null)
    {
        if (isset(static::$idCache[$data->getMasterProductId()->getHost()]['parentId'])) {
            $data->getMasterProductId()->setEndpoint(static::$idCache[$data->getMasterProductId()->getHost()]['parentId']);
        }
        
        $masterId = $data->getMasterProductId()->getEndpoint();
        
        if (!empty($masterId)) {
            $this->addVarCombiAsVariation($data, $masterId);
            //$this->clearUnusedVariations();
            
            return $data;
        }
        
        $id = $data->getId()->getEndpoint();
        
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
        
        $savedProduct = parent::push($data, $dbObj);
        
        static::$idCache[$data->getId()->getHost()]['parentId'] = $savedProduct->getId()->getEndpoint();
        
        return $savedProduct;
    }
    
    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();
        
        if (!empty($id) && $id != '') {
            try {
                $this->db->query('DELETE FROM products WHERE products_id=' . $id);
                $this->db->query('DELETE FROM products_to_categories WHERE products_id=' . $id);
                $this->db->query('DELETE FROM products_description WHERE products_id=' . $id);
                $this->db->query('DELETE FROM products_images WHERE products_id=' . $id);
                $result = $this->db->query('SELECT options_values_id FROM products_attributes WHERE products_id=' . $id);
                foreach ($result as $item) {
                    if (isset($item['options_values_id'])) {
                        $this->db->query('DELETE FROM products_options_values WHERE products_options_values_id=' . $item['options_values_id']);
                        $this->db->query('DELETE FROM jtl_connector_link_products_option WHERE endpoint_id=' . $item['options_values_id']);
                    }
                }
                $this->db->query('DELETE FROM products_attributes WHERE products_id=' . $id);
                $this->db->query('DELETE FROM products_xsell WHERE products_id=' . $id . ' OR xsell_id=' . $id);
                $this->db->query('DELETE FROM specials WHERE products_id=' . $id);
                
                foreach ($this->getCustomerGroups() as $group) {
                    $this->db->query('DELETE FROM personal_offers_by_customers_status_' . $group['customers_status_id'] . ' WHERE products_id=' . $id);
                }
                
                $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id="' . $id . '"');
            } catch (\Exception $e) {
            }
        }
        
        return $data;
    }
    
    public function statistic()
    {
        $objs = $this->db->query("
          SELECT count(p.products_id) as count
          FROM products p
          LEFT JOIN jtl_connector_link_product l ON p.products_id = l.endpoint_id
          WHERE l.host_id IS NULL LIMIT 1
        ", ["return" => "object"]);
        
        if (isset($objs[0])) {
            $objs = $objs !== null ? intval($objs[0]->count) : 0;
        } else {
            Logger::write('No objects were found');
        }
        
        $combis = $this->db->query("
          SELECT count(a.options_values) as count
          FROM products_attributes a
          LEFT JOIN jtl_connector_link_products_option l ON a.options_values = l.endpoint_id
          WHERE l.host_id IS NULL LIMIT 1
        ", ["return" => "object"]);
        
        if (isset($combis[0])) {
            $combis = $combis !== null ? intval($combis[0]->count) : 0;
        } else {
            Logger::write('No varCobis were found');
        }
        
        $objs += $combis;
        
        return $objs;
    }
    
    protected function considerBasePrice($data)
    {
        return $data['products_vpe_status'] == 1 ? true : false;
    }
    
    protected function products_vpe($data)
    {
        $name = $data->getBasePriceUnitName();
        
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
                            
                            $this->db->deleteInsertRow($status, 'products_vpe', ['products_vpe_id', 'language_id'],
                                [$status->product_vpe_id, $status->language_id]);
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
                            
                            $this->db->deleteInsertRow($status, 'shipping_status',
                                ['shipping_status_id', 'langauge_id'],
                                [$status->shipping_status_id, $status->language_id]);
                        }
                        
                        return $id;
                    }
                }
            }
        }
        
        return '';
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
        return $this->replaceZero($data['manufacturers_id']);
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
        $sql = $this->db->query('SELECT r.tax_rate FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = ' . $this->shopConfig['settings']['STORE_COUNTRY'] . ' && r.tax_class_id=' . $data['products_tax_class_id']);
        
        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_rate FROM tax_rates WHERE tax_rates_id=' . $this->connectorConfig->tax_rate);
        }
        
        return floatval($sql[0]['tax_rate']);
    }
    
    protected function products_tax_class_id($data)
    {
        $sql = $this->db->query('SELECT r.tax_class_id FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = ' . $this->shopConfig['settings']['STORE_COUNTRY'] . ' && r.tax_rate=' . $data->getVat());
        
        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id=' . $this->connectorConfig->tax_rate);
        }
        
        return $sql[0]['tax_class_id'];
    }
    
    protected function products_quantity($data)
    {
        return round($data->getStockLevel()->getStockLevel());
    }
    
    protected function isMasterProduct($data)
    {
        $childCount = $this->db->query("SELECT COUNT(*) AS cnt FROM products_attributes WHERE products_id = " . $data['products_id']);
        $result = (int)$childCount[0]['cnt'] > 0 ? true : false;
        return $result;
    }
    
    protected function addVarCombiAsVariation($data, $masterId)
    {
        foreach ($data->getVariations()[0]->getValues()[0]->getI18ns() as $variationI18n){
            
            $i18nId = array_search($variationI18n, $data->getVariations()[0]->getValues()[0]->getI18ns());
            $langId = parent::locale2id($data->getVariations()[0]->getValues()[0]->getI18ns()[$i18nId]->getLanguageISO());
            
            $variationId = $this->db->query(
                sprintf("SELECT * FROM products_options WHERE language_id = %s",
                $langId
                )
            );
            
            if (count($variationId) == 0) {
                $this->db->query(
                    sprintf("INSERT IGNORE INTO  products_options (products_options_id, language_id, products_options_name, products_options_sortorder) VALUES (1, %s, 'Variation', 0)",
                    $langId
                    )
                );
            }
            
            if(isset(static::$idCache[$data->getId()->getHost()]['valuesId'])) {
                $variationOptionId = static::$idCache[$data->getId()->getHost()]['valuesId'];
            } else {
                $variationOptionId = (int)$this->db->query(
                    "SELECT products_options_values_id FROM products_options_values ORDER BY products_options_values_id DESC LIMIT 0,1"
                )[0]["products_options_values_id"];
                $variationOptionId = $variationOptionId >= 0 ? ($variationOptionId + 1) : 1;
            }
            $variationOptionName = "";
    
            $i = 0;
            foreach ($data->getVariations() as $var) {
                $variationOptionName .= $var->getValues()[0]->getI18ns()[$i18nId]->getName();
                if ($i < count($data->getVariations()) - 1 && count($data->getVariations()) > 1) {
                    $variationOptionName .= " | ";
                }
                $i++;
            }
    
            $result = $this->db->query(
                sprintf("SELECT * FROM jtl_connector_link_products_option WHERE host_id = %s AND endpoint_id LIKE '%%_%s'",
                    $data->getId()->getHost(), $langId
                )
            );
            
            if (count($result) > 0) {
                $variationOptionId = explode("_", $result[0]['endpoint_id'])[0];
                $this->db->query(
                    sprintf("UPDATE products_options_values SET products_options_values_name = '%s', products_options_values_sortorder = 0 WHERE products_options_values_id = %s AND language_id = %s",
                        $variationOptionName, $variationOptionId, $langId
                    )
                );
                
            } else {
                $this->db->query(
                    sprintf("INSERT INTO products_options_values (products_options_values_id, language_id, products_options_values_name, products_options_values_sortorder) VALUES (%s, %s, '%s', 0)",
                        $variationOptionId, $langId, $variationOptionName
                    )
                );
                $this->db->query(
                    sprintf("INSERT INTO jtl_connector_link_products_option (endpoint_id, host_id) VALUES ('%s', %s)",
                        $variationOptionId . "_" . $langId, $data->getId()->getHost()
                    )
                );
    
                static::$idCache[$data->getId()->getHost()]['valuesId'] = $variationOptionId;
            }

            $id = $this->db->query(
                sprintf("SELECT products_options_id FROM products_options WHERE language_id = %s ORDER BY products_options_id DESC LIMIT 0,1",
                    $langId
                )
            );
            if (isset($id[0]["products_options_id"])) {
                $id = ((int)$id[0]["products_options_id"]);
            }
    
            $price = $this->db->query("SELECT products_price FROM products WHERE products_id = " . $masterId);
            if(!end($data->getPrices())->getItems()[0] || !isset($price[0]['products_price'])){
                throw new \RuntimeException('The VarCombi price has not been set');
            }
    
            $price = (double)end($data->getPrices())->getItems()[0]->getNetPrice() - $price[0]['products_price'];
            if ($price >= 0) {
                $pricePrefix = "+";
            } else {
                $pricePrefix = "-";
                $price = $price * -1;
            }
            
            $result2 = $this->db->query(
                sprintf("SELECT * FROM products_attributes WHERE options_values_id = %s AND products_id = %s" ,
                    $variationOptionId, $masterId
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
                    sprintf("UPDATE products_attributes SET options_id = %s, options_values_price = %s, price_prefix = '%s', attributes_model = '%s', attributes_stock = %s, options_values_weight = %s, weight_prefix = '%s', sortorder = %s, attributes_ean = %s, attributes_vpe_id = %s, attributes_vpe_value = %s WHERE options_values_id = %s AND products_id = %s",
                        $id, (double)$price, $pricePrefix, $sku, $stock, $weight, $weightPrefix, $sort, empty($ean) ? 'null' : $ean, $vpe, $vpe, $variationOptionId, $masterId
                    )
                );
        
            } else {
                $this->db->query(
                    sprintf("INSERT IGNORE INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix, attributes_model, attributes_stock, options_values_weight, weight_prefix, sortorder, attributes_ean, attributes_vpe_id, attributes_vpe_value) VALUES (%s, %s, %s, %s, '%s', '%s', %s, %s, '%s', %s, %s, %s, %s)",
                        $masterId, $id, $variationOptionId, (double)$price, $pricePrefix, $sku, $stock, $weight, $weightPrefix, $sort, empty($ean) ? 'null' : $ean, $vpe, $vpe
                    )
                );
            }
        }
    }
    
    protected function clearUnusedVariations()
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
