<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class CrossSelling extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_xsell",
        "query" => "SELECT p.*, (
                SELECT COUNT(products_xsell.products_id) 
                FROM products_xsell 
                WHERE products_xsell.products_id = p.products_id
            ) xsells FROM products p
            LEFT JOIN jtl_connector_link l ON p.products_id = l.endpointId AND l.type = 64
            WHERE l.hostId IS NULL HAVING xsells > 0",
        "mapPull" => array(
            "productId" => "products_id",
            "items" => "CrossSellingItem|addItem"
        )        
    );

    public function push($data, $dbObj = null)
    {
        $id = $data->getProductId()->getEndpoint();

        if (!empty($id)) {
            $this->db->query('DELETE FROM products_xsell WHERE products_id='.$id);    
        
            foreach ($data->getItems() as $item) {
                foreach ($item->getProductIds() as $xsellid) {
                    $xsell = new \stdClass;
                    $xsell->products_id = $id;
                    $xsell->products_xsell_grp_name_id = $item->getCrossSellingGroupId()->getEndpoint();
                    $xsell->xsell_id = $xsellid->getEndpoint();

                    $this->db->insertRow($xsell, 'products_xsell');
                }
            }
        }

        return $data;
    }
}
