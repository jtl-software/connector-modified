<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class Product2Category extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_to_categories",
        "query" => 'SELECT *,CONCAT(products_id,"_",categories_id) AS id FROM products_to_categories WHERE products_id=[[products_id]]',
        "mapPull" => array(
        	"id" => "id",
        	"categoryId" => "categories_id",
        	"productId" => "products_id"
        ),
        "mapPush" => array(
            "categories_id" => "_categoryId",
            "products_id" => "_productId" 
        )
    );    
}