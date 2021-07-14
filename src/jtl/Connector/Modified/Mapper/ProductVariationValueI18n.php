<?php

namespace jtl\Connector\Modified\Mapper;

class ProductVariationValueI18n extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_options_values",
        "query" => 'SELECT * FROM products_options_values WHERE products_options_values_id=[[options_values_id]]',
        "getMethod" => "getI18ns",
        "mapPull" => [
            "productVariationValueId"   => "products_options_values_id",
            "name"                      => "products_options_values_name",
            "languageISO"               => null
        ]
    ];
    
    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
    
    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
