<?php
namespace jtl\Connector\Modified\Mapper;

class CrossSelling extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_xsell",
        "query" => "SELECT p.*, (
                SELECT COUNT(products_xsell.products_id) 
                FROM products_xsell 
                WHERE products_xsell.products_id = p.products_id
            ) xsells FROM products p            
            LEFT JOIN jtl_connector_link_crossselling l ON p.products_id = l.endpoint_id
            WHERE l.host_id IS NULL HAVING xsells > 0",
        "mapPull" => array(
            "id" => "products_id",
            "productId" => "products_id",
            "items" => "CrossSellingItem|addItem"
        )        
    );

    public function push($data, $dbObj = null)
    {
        $id = $data->getProductId()->getEndpoint();

        if (!empty($id)) {
            $this->db->query('DELETE FROM products_xsell WHERE products_id='.Product::extractParentId($id));
        
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

    public function delete($data)
    {
        $id = $data->getProductId()->getEndpoint();

        if (!empty($id) && $id != '') {
            try {
                $this->db->query('DELETE FROM products_xsell WHERE products_id="'.$id.'"');
            }
            catch(\Exception $e) {            
            }
        }

        return $data;
    }
}
