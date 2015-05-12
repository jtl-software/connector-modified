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
            "name" => "manufacturers_name"
        ),
        "mapPush" => array(
            "manufacturers_id" => "id",
            "manufacturers_name" => "name"
        )
    );

    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            try {
                $this->db->query('DELETE FROM manufacturers WHERE manufacturers_id='.$id);
                $this->db->query('DELETE FROM manufacturers_info WHERE manufacturers_id='.$id);

                $this->db->query('DELETE FROM jtl_connector_link WHERE type=32 && endpointId="'.$id.'"');
            }
            catch (\Exception $e) {                
            }
        }

        return $data;
    }

    public function push($data, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            $this->db->query('DELETE FROM manufacturers_info WHERE manufacturers_id='.$id);
        }
        /*
        $url = $data->getWebsiteUrl();

        if(!empty($url)) {
            $languages = $this->db->query('SELECT languages_id FROM languages');

            foreach ($languages as $language) {
                $this->db->query('INSERT INTO manufacturers_info SET manufacturers_id='.$id.', languages_id='.$language['languages_id'].', manufacturers_url="'.$url.'"');
            }
        }
        */
        return parent::push($data, $dbObj);
    }
}
