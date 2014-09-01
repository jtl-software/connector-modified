<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class Product extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products",
        "where" => "products_id",
        "identity" => "getId",
        "mapPull" => array(
        	"id" => "products_id",
			"ean" => "products_ean",
			"stockLevel" => "products_quantity",
			"sku" => "products_model",
            "sort" => "products_sort",
			"created" => "products_date_added",
			"availableFrom" => "products_date_available",
			"productWeight" => "products_weight",
			"manufacturerId" => null,
			"manufacturerNumber" => "products_manufacturers_model",
            "basePriceUnitId" => null,
			"basePriceDivisor" => "products_vpe_value",
			"isTopProduct" => "products_startpage",
            "considerStock" => null,
			"considerVariationStock" => null,
			"permitNegativeStock" => null,
			"vat" => null,
            "i18ns" => "ProductI18n|addI18n",
            "categories" => "Product2Category|addCategory",
            "prices" => "ProductPrice|addPrice",
            "specialPrices" => "ProductSpecialPrice|addSpecialPrice",
            "variations" => "ProductVariation|addVariation",
            "invisibilities" => "ProductInvisibility|addInvisibility"
        ),
        "mapPush" => array(
            "products_id" => "id",
            "products_ean" => "ean",
            "products_quantity" => "stockLevel",
            "products_model" => "sku",
            "products_sort" => "sort",
            "products_date_added" => "created",
            "products_date_available" => "availableFrom",
            "products_weight" => "productWeight",
            "manufacturers_id" => "manufacturerId",
            "products_manufacturers_model" => "manufacturerNumber",
            "products_vpe" => "basePriceUnitId",
            "products_vpe_value" => "basePriceDivisor",
            "products_startpage" => "isTopProduct",
            "products_tax_class_id" => null,
            "ProductI18n|addI18n" => "i18ns",
            "Product2Category|addCategory" => "categories",
            "ProductPrice|addPrice" => "prices",
            "ProductSpecialPrice|addSpecialPrice" => "specialPrices",
            "ProductVariation|addVariation" => "variations",
            "ProductInvisibility|addInvisibility|true" => "invisibilities"
        )
    );
    
    protected function manufacturerId($data) {
        return $this->replaceZero($data['manufacturers_id']);
    }
    
    protected function basePriceUnitId($data) {
        return $this->replaceZero($data['products_vpe']);
    }
    /*
    protected function availableFrom($data) {
        return !is_null($data['products_date_available']) ? $data['products_date_available'] : null;
    }
    */
    protected function considerStock($data)  {
        return $this->shopConfig['STOCK_CHECK'];
    }
    
    protected function considerVariationStock($data)  {
        return $this->shopConfig['ATTRIBUTE_STOCK_CHECK'];
    }
    
    protected function permitNegativeStock($data)  {
        return $this->shopConfig['STOCK_ALLOW_CHECKOUT'];
    }
    
    protected function vat($data) {
        $sqliteResult = $this->getSqlite()->query('SELECT value FROM options WHERE key="tax_rate"');
        $taxId = $sqliteResult->fetchColumn();
                
        $sql = $this->db->query('SELECT tax_rate FROM tax_rates WHERE tax_rates_id='.$taxId);
        return floatval($sql[0]['tax_rate']);
    }  

    protected function products_tax_class_id($data) {
        $sqliteResult = $this->getSqlite()->query('SELECT value FROM options WHERE key="tax_rate"');
        $taxId = $sqliteResult->fetchColumn();
    
        $sql = $this->db->query('SELECT tax_class_id FROM tax_rates WHERE tax_rates_id='.$taxId);
        return $sql[0]['tax_class_id'];
    }
}