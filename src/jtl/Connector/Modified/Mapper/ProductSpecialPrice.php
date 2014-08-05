<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductSpecialPrice extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "specials",
        "query" => "SELECT * FROM specials WHERE products_id=[[products_id]]",
        "mapPull" => array(
        	"id" => "specials_id",
            "productId" => "products_id",		
            "isActive" => null,
            "activeUntil" => "expires_date",	
            "stockLimit" => "specials_quantity",
            "considerStockLimit" => null,	
            "considerDateLimit" => null,
            "specialPrices" => "SpecialPrice|addSpecialPrice"
        ),
        "mapPush" => array(
            "specials_id" => "_id",
            "products_id" => "_productId",
            "status" => null,
            "expires_date" => null,
            "specials_quantity" => "_stockLimit"
        )
    );
    
    protected function isActive($data) {
        return (bool) $data['status'];
    }
    
    protected function considerStockLimit($data) {
        return ($data['specials_quantity'] == 0) ? false : true;
    }
    
    protected function considerDateLimit($data) {
        return ($data['expires_date'] == '0000-00-00 00:00:00') ? false : true;
    }    
}