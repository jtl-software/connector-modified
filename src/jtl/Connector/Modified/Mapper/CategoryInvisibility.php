<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\CategoryInvisibility as CategoryInvisibilityModel;
use jtl\Connector\Model\Identity;

class CategoryInvisibility extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    public function pull($data = null, $limit = null)
    {
        $return = [];

        if ($this->shopConfig['settings']['GROUP_CHECK'] == 1) {
            foreach ($data as $key => $value) {
                if (preg_match("/group_permission_([0-9]+)/", $key, $match) && $value == 0) {
                    $categoryInvisibility = new CategoryInvisibilityModel();
                    $categoryInvisibility->setCustomerGroupId(new Identity($match[1]));
                    $categoryInvisibility->setCategoryId(new Identity($data['categories_id']));

                    $return[] = $categoryInvisibility;
                }
            }
        }

        return $return;
    }

    public function push($data, $dbObj = null)
    {
        $inactiveGroups = [];

        foreach ($data->getInvisibilities() as $invisibility) {
            $inactiveGroups[] = $invisibility->getCustomerGroupId()->getEndpoint();
        }

        $groups = $this->db->query('SELECT customers_status_id FROM customers_status GROUP BY customers_status_id');

        foreach ($groups as $group) {
            $groupId = $group['customers_status_id'];
            $property = "group_permission_".$groupId;

            $dbObj->$property = in_array($groupId, $inactiveGroups) ? 0 : 1;
        }

        return $data->getInvisibilities();
    }
}
