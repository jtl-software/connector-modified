<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Model\CategoryInvisibility as CategoryInvisibilityModel;
use \jtl\Connector\Model\Identity;

class CategoryInvisibility extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    public function pull($data) {
        $return = [];
        
        if($this->shopConfig['GROUP_CHECK'] == 1) {
            foreach($data as $key => $value) {
                if(preg_match("/group_permission_([0-9]+)/",$key,$match) && $value == 0) {
                    $categoryInvisibility = new CategoryInvisibilityModel();                 
                    $categoryInvisibility->setCustomerGroupId(new Identity($match[1]));
                    $categoryInvisibility->setCategoryId(new Identity($data['categories_id']));
                    
                    $return[] = $categoryInvisibility;
                }            
            }
        }
        
        return $return;
    }
}