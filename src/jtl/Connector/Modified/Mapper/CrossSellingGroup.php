<?php
namespace jtl\Connector\Modified\Mapper;

class CrossSellingGroup extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_xsell_grp_name",
        "query" => "SELECT * FROM products_xsell_grp_name GROUP BY products_xsell_grp_name_id",
        "identity" => "getId",
        "getMethod" => "getCrossSellingGroups",
        "mapPull" => array(
            "id" => "products_xsell_grp_name_id",
            "i18ns" => "CrossSellingGroupI18n|addI18n",
        ),
    );
    
    public function push($data, $dbObj = null)
    {
        $result = [];
        $ids = [];
        foreach ($data->getCrossSellingGroups() as $group) {
            $id = $group->getId()->getEndpoint();
            if (empty($id)) {
                $nextId = $this->db->query('SELECT max(products_xsell_grp_name_id) + 1 AS nextID FROM products_xsell_grp_name');
                $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
            } else {
                $this->db->query('DELETE FROM products_xsell_grp_name WHERE products_xsell_grp_name_id='.$id);
            }
            $group->getId()->setEndpoint($id);
    
            /** @var \jtl\Connector\Model\CrossSellingGroupI18n $i18n */
            foreach ($group->getI18ns() as $i18n) {
                $i18n->setCrossSellingGroupId($group->getId());
                $i18ns[] = (new CrossSellingGroupI18n)->push($i18n);
            }
            $group->setI18ns($i18ns);
            
            $result[] = $group;
            $ids[] = $group->getId()->getEndpoint();
        }
        
        $ids = implode(",", $ids);
        
        $this->db->query(sprintf(" DELETE FROM products_xsell WHERE products_xsell_grp_name_id NOT IN (%s)",
            $ids
        ));
    
        $this->db->query(sprintf(" DELETE FROM products_xsell_grp_name WHERE products_xsell_grp_name_id NOT IN (%s)",
            $ids
        ));
        
          $this->db->query(sprintf(" DELETE FROM jtl_connector_link_crossselling_group WHERE endpoint_id NOT IN (%s)",
            $ids
        ));
        return $result;
    }
}
