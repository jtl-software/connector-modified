<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductVariationI18n;
use jtl\Connector\Model\ProductVariation;
use jtl\Connector\Model\ProductVariationValue;
use jtl\Connector\Model\ProductVariationValueI18n;

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
            "products_vpe_value"                       => "basePriceDivisor",
            "products_vpe_status"                      => null,
            "products_status"                          => "isActive",
            "products_startpage"                       => "isTopProduct",
            "products_tax_class_id"                    => null,
            "ProductI18n|addI18n"                      => "i18ns",
            "Product2Category|addCategory"             => "categories",
            "ProductPrice|addPrice"                    => "prices",
            "ProductSpecialPrice|addSpecialPrice"      => "specialPrices",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            "ProductVariation|addVariation"            => "variations",
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

                $masterVariations = [];

                $dbResult = (new \jtl\Connector\Modified\Mapper\ProductVariationValue())->pull(['products_id' => $parent->getId()->getEndpoint()], $limit);
                foreach ($dbResult as $varCombi) {
    
                    $varCombiAttr = $this->db->query(
                        sprintf("SELECT * FROM products_attributes WHERE options_values_id = %s AND products_id = %s",
                            $varCombi->getId()->getEndpoint(), $parent->getId()->getEndpoint()
                        )
                    );
    
                    if (isset($varCombiAttr[0])) {
                    
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
                        
                        $varCombiProduct = clone $parent;
                        $varCombiProduct->setId(new Identity($parent->getId()->getEndpoint().'_'.$varCombiAttr[0]['products_attributes_id']));
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

                        $variationId = $varCombiAttr[0]['options_id'];

                        $variation = new ProductVariation();
                        $variation->setId(new Identity($variationId, null));
                        $variation->setSort($varCombi->getSort());
                        $variation->setType("select");

                        if(!isset($masterVariations[$variationId])){
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
    
    public function push($data, $dbObj = null)
    {
        $useVarKombis = $this->connectorConfig->use_var_combi_logic;
        
        if (isset(static::$idCache[$data->getMasterProductId()->getHost()]['parentId'])) {
            $data->getMasterProductId()->setEndpoint(static::$idCache[$data->getMasterProductId()->getHost()]['parentId']);
        }
        
        $masterId = $data->getMasterProductId()->getEndpoint();
        $hostMasterId = $data->getMasterProductId()->getHost();
        $variations = $data->getVariations();
        
        if (!empty($masterId) && $useVarKombis) {
            $this->addVarCombiAsVariation($data, $masterId);
            
            return $data;
        } elseif ( ((!empty($hostMasterId) || $data->getIsMasterProduct()) && !$useVarKombis) || ($useVarKombis && !empty($variations) && !$data->getIsMasterProduct())){
            return null;
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
                if (Product::isVarCombi($id)){
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
            $objs = (int)$objs[0]->count;
        } else {
            Logger::write('No objects were found');
        }
        
        $combis = $this->db->query("
          SELECT count(a.products_attributes_id) as count
          FROM products_attributes a
          LEFT JOIN jtl_connector_link_product l ON a.products_attributes_id = l.endpoint_id
          WHERE l.host_id IS NULL LIMIT 1
        ", ["return" => "object"]);
        
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
        if($data->getBasePriceQuantity() !== 1.){
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
        
        return 0;
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
        return (int)$childCount[0]['cnt'] > 0;
    }
    
    protected function addVarCombiAsVariation($data, $masterId)
    {
        foreach ($data->getVariations()[0]->getValues()[0]->getI18ns() as $variationI18n){
            
            $i18nId = array_search($variationI18n, $data->getVariations()[0]->getValues()[0]->getI18ns());
            $langId = parent::locale2id($data->getVariations()[0]->getValues()[0]->getI18ns()[$i18nId]->getLanguageISO());

            $variationName = [];
            /** @var ProductVariation $variation */
            foreach ($data->getVariations() as $variation) {
                foreach($variation->getI18ns() as $variationI18N){
                    if ($langId === parent::locale2id($variationI18N->getLanguageISO())) {
                        $variationName[] = $variationI18N->getName();
                    }
                }
            }
            $productsOptionName = 'Variation';
            if (!empty($variationName)) {
                $productsOptionName = join(' / ', $variationName);
            }

            $variationId = $this->db->query(sprintf("SELECT * FROM products_options WHERE language_id = %s AND products_options_name = '%s'", $langId, $productsOptionName));

            if (count($variationId) == 0) {
                $this->db->DB()->begin_transaction();
                $maxId = $this->db->query("SELECT MAX(products_options_id) as maxId FROM products_options");
                $maxId = isset($maxId[0]['maxId']) ? $maxId[0]['maxId'] + 1 : 1;
                $this->db->query(
                    sprintf("INSERT IGNORE INTO products_options (products_options_id, language_id, products_options_name, products_options_sortorder) VALUES (%s, %s, '%s', 0)", $maxId, $langId,
                        $productsOptionName)
                );
                $this->db->commit();

                $id = $this->db->query(
                    sprintf("SELECT products_options_id FROM products_options WHERE language_id = %s AND products_options_name = '%s' ORDER BY products_options_id DESC LIMIT 0,1", $langId,
                        $productsOptionName)
                );
                if (isset($id[0]["products_options_id"])) {
                    $id = ((int)$id[0]["products_options_id"]);
                }
            } else {
                $id = $variationId[0]['products_options_id'];
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
                if(isset($var->getValues()[0]->getI18ns()[$i18nId])) {
                    $variationOptionName .= $var->getValues()[0]->getI18ns()[$i18nId]->getName();
                }
                
                if ($i < count($data->getVariations()) - 1 && count($data->getVariations()) > 1) {
                    $variationOptionName .= " | ";
                }
                $i++;
            }
    
            $result = $this->db->query(
                sprintf("SELECT * FROM jtl_connector_link_product WHERE host_id = %s",
                    $data->getId()->getHost()
                )
            );
    
            if (count($result) > 0) {
                $variationOptionId = Product::extractOptionValueId($result[0]['endpoint_id']);
            } else {
                $this->db->query(
                    sprintf("INSERT INTO jtl_connector_link_product (endpoint_id, host_id) VALUES ('%s', %s)",
                        Product::createProductEndpoint($masterId, $variationOptionId), $data->getId()->getHost()
                    )
                );
                static::$idCache[$data->getId()->getHost()]['valuesId'] = $variationOptionId;
            }
            
            $variationValue = new \stdClass();
            $variationValue->products_options_values_name = $variationOptionName;
            $variationValue->products_options_values_id = $variationOptionId;
            $variationValue->language_id = $langId;
            
            if (version_compare($this->shopConfig['db']['version'], '2.0.4', '>=')){
                $variationValue->products_options_values_sortorder = 0;
            }
            
            $this->db->deleteInsertRow($variationValue, 'products_options_values',
                ['products_options_values_id', 'langauge_id'],
                [$variationOptionId, $langId]);
    
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
                    sprintf("UPDATE products_attributes SET options_id = %s, options_values_price = %s, price_prefix = '%s', attributes_model = '%s', attributes_stock = %s, options_values_weight = %s, weight_prefix = '%s', sortorder = %s, attributes_ean = '%s', attributes_vpe_id = %s, attributes_vpe_value = %s WHERE options_values_id = %s AND products_id = %s",
                        $id, (double)$price, $pricePrefix, $sku, $stock, $weight, $weightPrefix, $sort, empty($ean) ? '' : $ean, $vpe, $vpe, $variationOptionId, $masterId
                    )
                );
        
            } else {
                $this->db->query(
                    sprintf("INSERT IGNORE INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix, attributes_model, attributes_stock, options_values_weight, weight_prefix, sortorder, attributes_ean, attributes_vpe_id, attributes_vpe_value) VALUES (%s, %s, %s, %s, '%s', '%s', %s, %s, '%s', %s, '%s', %s, %s)",
                        $masterId, $id, $variationOptionId, (double)$price, $pricePrefix, $sku, $stock, $weight, $weightPrefix, $sort, empty($ean) ? '' : $ean, $vpe, $vpe
                    )
                );
            }
    
            $pivotTableResult = $this->db->query(
                sprintf("SELECT * FROM products_options_values_to_products_options WHERE products_options_id = %s AND products_options_values_id = %s" ,
                    $id, $variationOptionId
                )
            );
    
            if (count($pivotTableResult) == 0) {
                $this->db->query(
                    sprintf("INSERT IGNORE INTO products_options_values_to_products_options (products_options_id, products_options_values_id) VALUES (%s, %s)",
                        $id, $variationOptionId
                    )
                );
            }
        }
    }
    
    public static function createProductEndpoint($parentId, $optionValueId)
    {
        return $parentId . "_" . $optionValueId;
    }
    
    public static function extractParentId($endpoint)
    {
        if (self::isVarCombi($endpoint)) {
            $data = explode('_', (string)$endpoint);
            return $data[0];
        } else
        {
            return $endpoint;
        }
    }
    
    public static function extractOptionValueId($endpoint)
    {
        if (self::isVarCombi($endpoint)) {
            $data = explode('_', (string)$endpoint);
            return $data[1];
        }
        else {
            throw new \Error($endpoint . ' is not a Valid VarKombi endpoint');
        }
    }
    
    public static function isVarCombi($endpoint)
    {
        $data = explode('_', (string)$endpoint);
        return isset($data[1]) ? true : false;
    }
}
