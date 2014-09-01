<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;
use \jtl\Connector\Model\ProductVariationValueExtraCharge as ProductVariationValueExtraChargeModel;

class ProductVariationValueExtraCharge extends BaseMapper
{
    public function pull($data) {
        $return = [];
        
        if($data['options_values_price'] != 0) {
            foreach($this->getCustomerGroups() as $groupId) {
                if($groupId == $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID']) {                
                    $extraCharge = new ProductVariationValueExtraChargeModel();
                    $extraCharge->setCustomerGroupId($this->identity($groupId['customers_status_id']));
                    $extraCharge->setProductVariationValueId($this->identity($data['products_attributes_id']));
                    $extraCharge->setExtraChargeNet(floatval($data['price_prefix'] == '-' ? $data['options_values_price'] * -1 : $data['options_values_price']));
        
                    $return[] = $extraCharge;
                }
            }
        }
        
        return $return;
    }
    
    public function push($parent,$dbObj) {
        foreach($parent->getExtraCharges() as $extraCharge) {
            if($extraCharge->getCustomerGroupId()->getEndpoint() == $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID']) {
                $dbObj->price_prefix = $extraCharge->getExtraChargeNet() < 0 ? '-' : '+';   
                $dbObj->options_values_price = abs($extraCharge->getExtraChargeNet());
            }            
        }        
    }
}