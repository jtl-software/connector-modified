<?php
namespace jtl\Connector\Modified\Mapper;

class ProductVariationValue extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_attributes",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] && options_id=[[options_id]]',
        "getMethod" => "getValues",
        "mapPull" => array(
            "id" => "options_values_id",
            "productVariationId" => "options_id",
            "extraWeight" => null,
            "sku" => "attributes_model",
            "sort" => "sortorder",
            "stockLevel" => "attributes_stock",
            "i18ns" => "ProductVariationValueI18n|addI18n",
            "extraCharges" => "ProductVariationValueExtraCharge|addExtraCharge"
        )
    );

    protected function extraWeight($data)
    {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
}
