<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrderItemVariation extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT *,[[products_id]] AS products_id FROM orders_products_attributes WHERE orders_products_id=[[orders_products_id]]",
        "mapPull" => array(
        	"id" => "orders_products_attributes_id",
            "customerOrderItemId" => "orders_products_id",
            "productVariationId" => null,
            "productVariationValueId" => null,	
            "productVariationName" => "products_options",
            "productVariationValueName" => "products_options_values",	
            "surcharge" => null
        ),
        "mapPush" => array(
            "orders_products_attributes_id" => "_id",
            "orders_products_id" => "_customerOrderItemId",
            "products_options" => "_productVariationName",
            "products_options_values" => "_productVariationValueName",
            "price_prefix" => null,
            "options_values_price" => null,
            "orders_products_options_id" => null,
            "orders_products_options_values_id" => null
        )
    );
    
    protected function surcharge($data) {
        return ($data['price_prefix'] == '+') ? $data['options_values_price'] : $data['options_values_price'] * -1;
    }
    
    protected function productVariationId($data) {
        return $data['products_id'].'-'.$data['orders_products_options_id'];
    }
    
    protected function productVariationValueId($data) {
        return $data['products_id'].'-'.$data['orders_products_options_id'].'-'.$data['orders_products_options_values_id'];
    }         
}