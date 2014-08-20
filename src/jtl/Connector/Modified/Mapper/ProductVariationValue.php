<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariationValue extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] && options_id=[[options_id]]',
        "mapPull" => array(
        	"id" => "products_attributes_id",
            "productVariationId" => "options_id",
            "extraWeight" => null,
            "sku" => "attributes_model",
            "sort" => "sortorder",
            "stockLevel" => "attributes_stock",
            "i18ns" => "ProductVariationValueI18n|addI18n",
            "extraCharges" => "ProductVariationValueExtraCharge|addExtraCharge"
        ),
        "mapPush" => array(     
        )
    );    
    
    protected function extraWeight($data) {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}