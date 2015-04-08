<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class Manufacturer extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "manufacturers",
        "query" => "SELECT m.* FROM manufacturers m
            LEFT JOIN jtl_connector_link l ON m.manufacturers_id = l.endpointId AND l.type = 32
            WHERE l.hostId IS NULL",
        "where" => "manufacturers_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "manufacturers_id",
            "i18ns" => "ManufacturerI18n|addI18n"
        ),
        "mapPush" => array(
            "manufacturers_id" => "id",
            "ManufacturerI18n|addI18n|true" => "i18ns"
        )
    );

    public function delete($data)
    {
        $this->db->query('DELETE FROM manufacturers WHERE manufacturers_id='.$data->getId()->getEndpoint());
        $this->db->query('DELETE FROM manufacturers_info WHERE manufacturers_id='.$data->getId()->getEndpoint());

        return $data;
    }
}
