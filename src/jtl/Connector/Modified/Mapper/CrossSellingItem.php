<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\CrossSellingItem as CrossSellingItemModel;

class CrossSellingItem extends AbstractMapper
{
    public function pull($data)
    {
        $query = 'SELECT x.* FROM products_xsell x WHERE x.products_id ='.$data['products_id'];
        $results = $this->db->query($query);
        
        foreach ($results as $xsell) {
            $groups[$xsell['products_xsell_grp_name_id']][] = $this->identity($xsell['xsell_id']);
        }
        
        $return = [];

        foreach ($groups as $group => $ids) {
            $item = new CrossSellingItemModel();
            $item->setCrossSellingGroupId($this->identity($group));
            $item->setProductIds($ids);

            $return[] = $item;
        }
        
        return $return;
    }
}
