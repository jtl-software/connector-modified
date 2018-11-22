<?php
/**
 * Created by PhpStorm.
 * User: Niklas
 * Date: 14.11.2018
 * Time: 12:56
 */

namespace jtl\Connector\Modified\Mapper;

class ProductVariationCombinationValueI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options_values",
        "query" => 'SELECT * FROM products_options_values WHERE products_options_values_id=[[options_values_id]]',
        "getMethod" => "getI18ns",
        "mapPull" => array(
            "productVariationValueId"   => "products_options_values_id",
            "name"                      => "products_options_values_name",
            "languageISO"               => null
        )
    );
    
    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}