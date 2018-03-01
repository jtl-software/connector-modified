<?php
namespace jtl\Connector\Modified\Mapper;

class Product extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products",
        "query" => "SELECT p.* FROM products p
            LEFT JOIN jtl_connector_link_product l ON p.products_id = l.endpoint_id 
            WHERE l.host_id IS NULL",
        "where" => "products_id",
        "identity" => "getId",
        "mapPull" => array(
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
            "considerStock" => null,
            "considerVariationStock" => null,
            "permitNegativeStock" => null,
            "i18ns" => "ProductI18n|addI18n",
            "categories" => "Product2Category|addCategory",
            "prices" => "ProductPrice|addPrice",
            "specialPrices" => "ProductSpecialPrice|addSpecialPrice",
            "variations" => "ProductVariation|addVariation",
            "invisibilities" => "ProductInvisibility|addInvisibility",
            "attributes" => "ProductAttr|addAttribute",
            "vat" => null
        ),
        "mapPush" => array(
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
            "ProductVariation|addVariation" => "variations",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            "ProductAttr|addAttribute|true" => "attributes",
            "products_image" => null,
            "products_shippingtime" => null,
            "products_price" => null
        )
    );

    public function push($data, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id)) {
            foreach ($this->getCustomerGroups() as $group) {
                $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$id);
            }

            $this->db->query('DELETE FROM specials WHERE products_id='.$id);
        }

        return parent::push($data, $dbObj);
    }

    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            try {
                $this->db->query('DELETE FROM products WHERE products_id='.$id);
                $this->db->query('DELETE FROM products_to_categories WHERE products_id='.$id);
                $this->db->query('DELETE FROM products_description WHERE products_id='.$id);
                $this->db->query('DELETE FROM products_images WHERE products_id='.$id);
                $this->db->query('DELETE FROM products_attributes WHERE products_id='.$id);
                $this->db->query('DELETE FROM products_xsell WHERE products_id='.$id.' OR xsell_id='.$id);
                $this->db->query('DELETE FROM specials WHERE products_id='.$id);

                foreach ($this->getCustomerGroups() as $group) {
                    $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$id);
                }

                $this->db->query('DELETE FROM jtl_connector_link_product WHERE endpoint_id="'.$id.'"');
            }
            catch (\Exception $e) {                
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
        ", array("return" => "object"));

        return $objs !== null ? intval($objs[0]->count) : 0;
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
                            $status->products_vpe_name = $data->getBasePriceQuantity().' '.$name;

                            $this->db->deleteInsertRow($status, 'products_vpe', array('products_vpe_id', 'language_id'), array($status->product_vpe_id, $status->language_id));
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
                $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id='.$language_id);

                if ($dbResult[0]['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE']) {
                    $sql = $this->db->query('SELECT shipping_status_id FROM shipping_status WHERE language_id='.$language_id.' && shipping_status_name="'.$name.'"');
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

                            $this->db->deleteInsertRow($status, 'shipping_status', array('shipping_status_id', 'langauge_id'), array($status->shipping_status_id, $status->language_id));
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
            $img = $this->db->query('SELECT products_image FROM products WHERE products_id ='.$id);
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
        $check = $this->db->query('SELECT products_id FROM products_attributes WHERE products_id='.$data['products_id']);
        
        return count($check) > 0 ? true : false;
    }

    protected function permitNegativeStock($data)
    {
        return $this->shopConfig['settings']['STOCK_ALLOW_CHECKOUT'];
    }

    protected function vat($data)
    {
        $sql = $this->db->query('SELECT r.tax_rate FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = '.$this->shopConfig['settings']['STORE_COUNTRY'].' && r.tax_class_id='.$data['products_tax_class_id']);

        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_rate FROM tax_rates WHERE tax_rates_id='.$this->connectorConfig->tax_rate);
        }

        return floatval($sql[0]['tax_rate']);
    }

    protected function products_tax_class_id($data)
    {
        $sql = $this->db->query('SELECT r.tax_class_id FROM zones_to_geo_zones z LEFT JOIN tax_rates r ON z.geo_zone_id=r.tax_zone_id WHERE z.zone_country_id = '.$this->shopConfig['settings']['STORE_COUNTRY'].' && r.tax_rate='.$data->getVat());
        
        if (empty($sql)) {
            $sql = $this->db->query('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id='.$this->connectorConfig->tax_rate);
        }

        return $sql[0]['tax_class_id'];
    }

    protected function products_quantity($data)
    {
        return round($data->getStockLevel()->getStockLevel());
    }
}
