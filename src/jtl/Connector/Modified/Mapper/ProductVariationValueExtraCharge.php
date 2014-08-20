<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;
use \jtl\Connector\Model\ProductVariationValueExtraCharge as ProductVariationValueExtraChargeModel;

class ProductVariationValueExtraCharge extends BaseMapper
{
    protected $mapperConfig = array(        
    );
    
    public function pull($data) {
        $return = [];
        
        if($data['options_values_price'] != 0) {
            foreach($this->getCustomerGroups() as $groupId) {
                $extraCharge = new ProductVariationValueExtraChargeModel();
                $extraCharge->setCustomerGroupId($this->identity($groupId['customers_status_id']));
                $extraCharge->setProductVariationValueId($this->identity($data['products_attributes_id']));
                $extraCharge->setExtraChargeNet(floatval($data['price_prefix'] == '-' ? $data['options_values_price'] * -1 : $data['options_values_price']));
    
                $return[] = $extraCharge;
            }
        }
        
        return $return;
    }
}