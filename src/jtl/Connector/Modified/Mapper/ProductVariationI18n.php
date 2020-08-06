<?php
namespace jtl\Connector\Modified\Mapper;

class ProductVariationI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_options WHERE products_options_id=[[options_id]]',
        "mapPull" => array(
            "productVariationId" => "products_options_id",
            "name" => "products_options_name",
            "languageISO" => null
        )
    );

    protected function languageISO($data)
    {
        return $this->id2locale($data['language_id']);
    }
}
