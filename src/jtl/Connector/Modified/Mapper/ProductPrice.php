<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductPrice extends BaseMapper
{
    protected $mapperConfig = array(
        "getMethod" => "getPrices",
        "mapPull" => array(
        	"customerGroupId" => "customers_status_id",
        	"productId" => "products_id",
            "items" => "ProductPriceItem|addItem"
        ),
        "mapPush" => array(
            "ProductPriceItem|addItem" => "items"
        )
    );
	
    public function pull($data) {
        $customerGroups = $this->getCustomerGroups();
        
        $return = [];
        $defaultSet = false;
        
        foreach($customerGroups as $groupData) {
            $groupData['products_id'] = $data['products_id'];
            $groupData['default_price'] = $data['products_price'];
            
            $return[] = $this->generateModel($groupData);       
        }       
        
        return $return;
    } 
}
?>