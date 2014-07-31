<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class Currency extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "currencies",
    	"mapPull" => array(
        	"id" => "currencies_id",
        	"name" => "title",
        	"factor" => "value",
        	"decimalSeparator" => "decimal_point",
        	"thousandsSeparator" => "thousands_point",
    	    "isDefault" => null
        )
    );    
    
    protected function isDefault($data) {
        return $data['code'] == $this->shopConfig['DEFAULT_CURRENCY'] ? true : false;
    }
}