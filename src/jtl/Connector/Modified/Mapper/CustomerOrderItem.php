<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrderItem extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders_products",
        "query" => "SELECT * FROM orders_products WHERE orders_id=[[orders_id]]",
        "mapPull" => array(
        	"id" => "orders_products_id",
        	"productId" => "products_id",
        	"customerOrderId" => "orders_id",
        	"quantity" => "products_quantity",
        	"name" => "products_name",
        	"price" => null,
        	"vat" => "products_tax",
        	"sku" => "products_model",
            "variations" => "CustomerOrderItemVariation|addVariation"
        ),
        "mapPush" => array(
            "orders_products_id" => "_id",
            "products_id" => "_productId",
            "orders_id" => "_customerOrderId",
            "products_quantity" => "_quantity",
            "products_name" => "_name",
            "products_price" => null,
            "products_tax" => "_vat",
            "products_model" => "_sku",
            "allow_tax" => null,
            "final_price" => null
        )
    );
    
    protected function price($data) {
        return ($data['products_price']/(100+$data['products_tax'])) * 100;
    }
    
    /*
    protected function products_price($data) {
        return ($data->_price/100) * (100+$data->_vat);
    }
    
    protected function final_price($data) {
        return (($data->_price/100) * (100+$data->_vat)) * $data->_quantity;
    }
    
    protected function allow_tax($data) {
        return 1;
    } 
    */   
}
?>