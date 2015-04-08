<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class ProductCrossSelling extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_xsell",
        "getMethod" => "getCrossSellings",
        "identity" => "getId",
        "where" => "products_id",
        "query" => 'SELECT * FROM products_xsell WHERE products_id=[[products_id]]',
        "mapPull" => array(
            "id" => "ID",
            "productId" => "products_id",
            "crossProductId" => "xsell_id",
            "crossSellingGroupId" => "products_xsell_grp_name_id"
        ),
        "mapPush" => array(
            "ID" => "id",
            "products_id" => "productId",
            "xsell_id" => "crossProductId",
            "products_xsell_grp_name_id" => "crossSellingGroupId"
        )
    );

    public function push($parent, $dbObj)
    {
        $id = $parent->getId()->getEndpoint();

        if (!empty($id)) {
            $this->db->query('DELETE FROM products_xsell WHERE products_id='.$id);
        }

        return parent::push($parent, $dbObj);
    }
}
