<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class Unit extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT products_vpe_id FROM products_vpe GROUP BY products_vpe_id",
        "table" => "products_vpe",
        "where" => "products_vpe_id",
        "getMethod" => "getUnits",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "products_vpe_id",
            "i18ns" => "UnitI18n|addI18n"
        ),
        "mapPush" => array(
        	"UnitI18n|addI18n" => "i18ns",
        )
    );

    public function push($data, $dbObj = null)
    {
        $nextId = $this->db->query('SELECT max(products_vpe_id) + 1 AS nextID FROM products_vpe');
        $nextId = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];

        foreach ($data->getUnits() as $unit) {
            $id = $unit->getId()->getEndpoint();

            if (empty($id) || $id == '') {
                $unit->getId()->setEndpoint($nextId);

                foreach ($unit->getI18ns() as $i18n) {
                    $i18n->setUnitId($unit->getId());
                }

                $nextId++;
            } else {
                $this->db->query('DELETE FROM products_vpe WHERE products_vpe_id='.$id);        
            }
        }

        return parent::push($data, $dbObj);
    }
}
