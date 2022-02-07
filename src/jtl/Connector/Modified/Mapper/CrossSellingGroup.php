<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\DataModel;

class CrossSellingGroup extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_xsell_grp_name",
        "query" => "SELECT * FROM products_xsell_grp_name GROUP BY products_xsell_grp_name_id",
        "identity" => "getId",
        "getMethod" => "getCrossSellingGroups",
        "mapPull" => [
            "id" => "products_xsell_grp_name_id",
            "i18ns" => "CrossSellingGroupI18n|addI18n",
        ],
    ];

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $result = [];
        $ids = [];
        foreach ($model->getCrossSellingGroups() as $group) {
            $id = $group->getId()->getEndpoint();
            if (empty($id)) {
                $nextId = $this->db->query('SELECT max(products_xsell_grp_name_id) + 1 AS nextID FROM products_xsell_grp_name');
                $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
            } else {
                $this->db->query('DELETE FROM products_xsell_grp_name WHERE products_xsell_grp_name_id='.$id);
            }
            $group->getId()->setEndpoint($id);

            $i18ns = [];
            /** @var \jtl\Connector\Model\CrossSellingGroupI18n $i18n */
            foreach ($group->getI18ns() as $i18n) {
                $i18n->setCrossSellingGroupId($group->getId());
                $i18ns[] = (new CrossSellingGroupI18n($this->db, $this->shopConfig, $this->connectorConfig))->push($i18n);
            }
            $group->setI18ns($i18ns);
            
            $result[] = $group;
            $ids[] = $group->getId()->getEndpoint();
        }
        
        $ids = implode(",", $ids);
        
        if (!empty($ids)) {
            $this->db->query(sprintf(
                " DELETE FROM products_xsell WHERE products_xsell_grp_name_id NOT IN (%s)",
                $ids
            ));
    
            $this->db->query(sprintf(
                " DELETE FROM products_xsell_grp_name WHERE products_xsell_grp_name_id NOT IN (%s)",
                $ids
            ));
    
            $this->db->query(sprintf(
                " DELETE FROM jtl_connector_link_crossselling_group WHERE endpoint_id NOT IN (%s)",
                $ids
            ));
        } else {
            $this->db->query("DELETE j.*, p.*, g.* FROM jtl_connector_link_crossselling_group j LEFT JOIN products_xsell p ON j.endpoint_id = p.products_xsell_grp_name_id LEFT JOIN products_xsell_grp_name g ON j.endpoint_id = g.products_xsell_grp_name_id");
        }
        
        return $result;
    }
}
