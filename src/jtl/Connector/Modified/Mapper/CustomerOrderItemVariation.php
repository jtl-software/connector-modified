<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrderItemVariation extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders_products_attributes",
        "query" => "SELECT *,[[products_id]] AS products_id FROM orders_products_attributes WHERE orders_products_id=[[orders_products_id]]",
        "where" => "orders_products_attributes_id",
        "getMethod" => "getVariations",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "orders_products_attributes_id",
            "customerOrderItemId" => "orders_products_id",
            "productVariationId" => "orders_products_options_id",
            "productVariationValueId" => "orders_products_options_values_id",
            "productVariationName" => "products_options",
            "productVariationValueName" => "products_options_values",
            "surcharge" => null,
        ),
        "mapPush" => array(
            "orders_products_attributes_id" => "id",
            "orders_products_id" => "customerOrderItemId",
            "products_options" => "productVariationName",
            "products_options_values" => "productVariationValueName",
            "price_prefix" => null,
            "options_values_price" => null,
            "orders_products_options_id" => null,
            "orders_products_options_values_id" => null,
            "orders_id" => null,
        ),
    );

    protected function surcharge($data)
    {
        return $data['price_prefix'] == '+' ? $data['options_values_price'] : $data['options_values_price'] * -1;
    }

    protected function price_prefix($data)
    {
        return $data->getSurcharge() < 0 ? '-' : '+';
    }

    protected function options_values_price($data)
    {
        return abs($data->getSurcharge());
    }

    protected function orders_products_options_id($data)
    {
        return $data->getProductVariationId()->getEndpoint();
    }

    protected function orders_products_options_values_id($data)
    {
        return $data->getProductVariationValueId()->getEndpoint();
    }

    protected function orders_id($data, $model, $parent)
    {
        return $parent->getCustomerOrderId()->getEndpoint();
    }
}
