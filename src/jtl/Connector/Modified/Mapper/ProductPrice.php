<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductPrice extends BaseMapper
{
    protected $mapperConfig = array(
        "mapPull" => array(
        	"customerGroupId" => "groupId",
        	"productId" => "products_id",
        	"netPrice" => "personal_offer",
        	"quantity" => "quantity"
        ),
        "mapPush" => array(
            "products_id" => "_productId",
            "personal_offer" =>"_netPrice",
            "quantity" => "_quantity"
        )
    );
	
    public function pull($data) {
        $customerGroups = $this->getCustomerGroups();
        
        $return = [];
        $defaultSet = false;
        
        foreach($customerGroups as $groupId) {
            $pricesData = $this->db->query("SELECT *,".$groupId['customers_status_id']." AS groupId FROM personal_offers_by_customers_status_".$groupId['customers_status_id']." WHERE products_id = ".$data['products_id']." && personal_offer > 0");
            
            foreach($pricesData as $priceData) {
                $return[] = $this->generateModel($priceData);
                if($priceData['groupId'] == $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID'] && $priceData['quantity'] == 1) $defaultSet = true;
            }            
        }
        
        if(!$defaultSet) {
            $defaultPrice = array(
                'groupId' => $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID'],
                'products_id' => $data['products_id'],
                'personal_offer' => $data['products_price'],
                'quantity' => 1
            );

            $return[] = $this->generateModel($defaultPrice);
        }
        
        return $return;
    }
    
	private function getCustomerGroups() {
	    return $this->db->query("SELECT customers_status_id FROM customers_status GROUP BY customers_status_id ORDER BY customers_status_id");	    
	}
}
?>