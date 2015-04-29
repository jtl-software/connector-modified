<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariationValueI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options_values",
        "query" => 'SELECT * FROM products_options_values WHERE products_options_values_id=[[options_values_id]]',
        "getMethod" => "getI18ns",
        "mapPull" => array(
            "productVariationValueId" => "products_options_values_id",
            "name" => "products_options_values_name",
            "languageISO" => null
        )
    );

    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
