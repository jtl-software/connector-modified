<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\ProductStockLevel;

class Product extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products",
        "query" => "SELECT p.* FROM products p
            LEFT JOIN jtl_connector_link l ON p.products_id = l.endpointId AND l.type = 64
            WHERE l.hostId IS NULL",
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
            "basePriceUnitId" => null,
            "basePriceDivisor" => "products_vpe_value",
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
            "crossSellings" => "ProductCrossSelling|addCrossSelling",
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
            "products_vpe" => "basePriceUnitId",
            "products_vpe_value" => "basePriceDivisor",
            "products_status" => "isActive",
            "products_startpage" => "isTopProduct",
            "products_tax_class_id" => null,
            "ProductI18n|addI18n" => "i18ns",
            "Product2Category|addCategory" => "categories",
            "ProductPrice|addPrice" => "prices",
            "ProductSpecialPrice|addSpecialPrice" => "specialPrices",
            "ProductVariation|addVariation" => "variations",
            "ProductInvisibility|addInvisibility|true" => "invisibilities",
            "ProductCrossSelling|addCrossSelling" => "crossSellings",
            "products_image" => null
        )
    );

    public function push($data, $dbObj = null)
    {
        if (!is_null($data->getId()->getEndpoint())) {
            foreach ($this->getCustomerGroups() as $group) {
                $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$data->getId()->getEndpoint());
            }
        }

        return parent::push($data, $dbObj);
    }

    public function delete($data)
    {
        $this->db->query('DELETE FROM products WHERE products_id='.$data->getId()->getEndpoint());
        $this->db->query('DELETE FROM products_to_categories WHERE products_id='.$data->getId()->getEndpoint());
        $this->db->query('DELETE FROM products_description WHERE products_id='.$data->getId()->getEndpoint());
        $this->db->query('DELETE FROM products_images WHERE products_id='.$data->getId()->getEndpoint());
        $this->db->query('DELETE FROM products_attributes WHERE products_id='.$data->getId()->getEndpoint());
        $this->db->query('DELETE FROM products_xsell WHERE products_id='.$data->getId()->getEndpoint().' OR xsell_id='.$data->getId()->getEndpoint());

        foreach ($this->getCustomerGroups() as $group) {
            $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$data->getId()->getEndpoint());
        }

        $this->db->query('DELETE FROM jtl_connector_link WHERE type=64 && endpointId='.$data->getId()->getEndpoint());

        return $data;
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

    protected function manufacturerId($data)
    {
        return $this->replaceZero($data['manufacturers_id']);
    }

    protected function basePriceUnitId($data)
    {
        return $this->replaceZero($data['products_vpe']);
    }

    protected function considerStock($data)
    {
        return $this->shopConfig['settings']['STOCK_CHECK'];
    }

    protected function considerVariationStock($data)
    {
        return $this->shopConfig['settings']['ATTRIBUTE_STOCK_CHECK'];
    }

    protected function permitNegativeStock($data)
    {
        return $this->shopConfig['settings']['STOCK_ALLOW_CHECKOUT'];
    }

    protected function vat($data)
    {
        $sql = $this->db->query('SELECT tax_rate FROM tax_rates WHERE tax_rates_id='.$this->connectorConfig->tax_rate);
        return floatval($sql[0]['tax_rate']);
    }

    protected function products_tax_class_id($data)
    {
        $sql = $this->db->query('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id='.$this->connectorConfig->tax_rate);
        return $sql[0]['tax_class_id'];
    }

    protected function products_quantity($data)
    {
        return round($data->getStockLevel()->getStockLevel());
    }
}
