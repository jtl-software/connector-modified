<?php
/**
 * Created by PhpStorm.
 * User: Niklas
 * Date: 14.11.2018
 * Time: 12:56
 */

namespace jtl\Connector\Modified\Mapper;

class ProductVariationCombinationValue extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_attributes",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] && options_id=[[options_id]]',
        "getMethod" => "getValues",
        "mapPull" => array(
            "id"                    => "options_values_id",
            "productVariationId"    => "options_id",
            "extraWeight"           => null,
            "sku"                   => "attributes_model",
            "ean"                   => "attributes_ean",
            "sort"                  => "sortorder",
            "stockLevel"            => "attributes_stock",
            "i18ns"                 => "ProductVariationValueI18n|addI18n",
            "extraCharges"          => "ProductVariationValueExtraCharge|addExtraCharge"
        )
    
    
    );
    
    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}