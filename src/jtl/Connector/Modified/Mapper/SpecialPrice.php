<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class SpecialPrice extends BaseMapper
{
    protected $mapperConfig = array(
        "mapPull" => array(
            "customerGroupId" => null,
            "productSpecialPriceId" => "specials_id",
            "priceNet" => "specials_new_products_price"
        )
    );
   
    public function pull($data) {
       return array($this->generateModel($data));
    }
    
    protected function customerGroupId($data) {
        return $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID'];
    }
}