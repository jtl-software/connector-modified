<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariation extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] GROUP BY options_id',
        "mapPull" => array(
        	"id" => "options_id",
        	"productId" => "products_id",
        	"type" => null,
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue"
        ),
        "mapPush" => array(     
        )
    );    
    
    protected function type($data) {
        return "select";
    }
}