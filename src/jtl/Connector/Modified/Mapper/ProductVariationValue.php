<?php

namespace jtl\Connector\Modified\Mapper;

class ProductVariationValue extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_attributes",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]]',
        "getMethod" => "getValues",
        "mapPull" => [
            "id"                    => "options_values_id",
            "productVariationId"    => "options_id",
            "ean"                   => "attributes_ean",
            "stockLevel"            => "attributes_stock",
            "i18ns"                 => "ProductVariationValueI18n|addI18n"
        ]
    ];
    
    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}
