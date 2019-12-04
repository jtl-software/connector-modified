<?php

namespace jtl\Connector\Modified\Mapper;

class Manufacturer extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "manufacturers",
        "query" => "SELECT m.* FROM manufacturers m
            LEFT JOIN jtl_connector_link_manufacturer l ON m.manufacturers_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "manufacturers_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "manufacturers_id",
            "name" => "manufacturers_name",
            "websiteUrl" => null
        ),
        "mapPush" => array(
            "manufacturers_id" => "id",
            "manufacturers_name" => "name"
        )
    );

    protected function websiteUrl($data)
    {
        $result = $this->db->query('SELECT m.manufacturers_url, l.languages_id FROM languages l LEFT JOIN manufacturers_info m ON m.languages_id=l.languages_id WHERE m.manufacturers_id=' . $data['manufacturers_id'] . ' && l.code="' . $this->shopConfig['settings']['DEFAULT_LANGUAGE'] . '"');

        if (count($result) > 0) {
            return $result[0]['manufacturers_url'];
        }
    }

    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            try {
                $this->db->query('DELETE FROM manufacturers WHERE manufacturers_id=' . $id);
                $this->db->query('DELETE FROM manufacturers_info WHERE manufacturers_id=' . $id);

                $this->db->query('DELETE FROM jtl_connector_link_manufacturer WHERE endpoint_id="' . $id . '"');
            } catch (\Exception $e) {
            }
        }

        return $data;
    }

    public function push($data, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id) && $id != '') {
            $this->db->query('DELETE FROM manufacturers_info WHERE manufacturers_id=' . $id);
        }

        $url = $data->getWebsiteUrl();

        $return = parent::push($data, $dbObj);

        $newId = $return->getId()->getEndpoint();

        if (!empty($url) && !empty($newId)) {
            $languages = $this->db->query('SELECT languages_id FROM languages');

            foreach ($languages as $language) {
                $infoData = [
                    'manufacturers_id' => $newId,
                    'languages_id' => $language['languages_id'],
                    'manufacturers_description' => '',
                    'manufacturers_meta_title' => '',
                    'manufacturers_meta_description' => '',
                    'manufacturers_meta_keywords' => '',
                    'manufacturers_url' => $url,
                ];

                $sql = sprintf('INSERT INTO `manufacturers_info` (%s) VALUES (%s)', '`' . implode('`,`', array_keys($infoData)) . '`', '"' . implode('","', array_values($infoData)) . '"');

                $this->db->query($sql);
            }
        }

        return $return;
    }
}
