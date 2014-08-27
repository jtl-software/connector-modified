<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductPrice extends BaseMapper
{
    protected $mapperConfig = array(
        "getMethod" => "getPrices",
        "mapPull" => array(
        	"customerGroupId" => "groupId",
        	"productId" => "products_id",
        	"netPrice" => "personal_offer",
        	"quantity" => "quantity"
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

    public function push($data) {
        $productId = $data->getId()->getEndpoint();
        
        if(!empty($productId)) {
            foreach($this->getCustomerGroups() as $group) {
                $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$productId);
            }
        }
        
        foreach($data->getPrices() as $price) {
            $obj = new \stdClass();
            
            if($price->getQuantity() == 1 && $price->getCustomerGroupId()->getEndpoint() == $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID']) {
                $obj->products_price = $price->getNetprice();
                $this->db->updateRow($obj,'products','products_id',$productId);                
            }
            else {
                $obj->products_id = $productId;
                $obj->personal_offer = $price->getNetprice();
                $obj->quantity = $price->getQuantity();
                
                $this->db->insertRow($obj,'personal_offers_by_customers_status_'.$price->getCustomerGroupId()->getEndpoint());
            }
        }
    }
}
?>