<?php
namespace jtl\Connector\Modified\Checksum;

use jtl\Connector\Checksum\IChecksumLoader;
use jtl\Connector\Core\Database\Mysql;

class ChecksumLoader implements IChecksumLoader
{
    protected $db;

    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    public function read($endpointId, $type)
    {
        $dbResult = $this->db->query('SELECT checksum FROM jtl_connector_product_checksum WHERE endpoint_id = "'.$endpointId.'" AND type = '.$type);

        $checksum = (count($dbResult) > 0) ? $dbResult[0]['checksum'] : null;

        return $checksum;
    }

    public function delete($endpointId, $type)
    {
        $this->db->query('DELETE FROM jtl_connector_product_checksum WHERE type='.$type.' && endpoint_id="'.$endpointId.'"');
    }

    public function write($endpointId, $type, $checksum)
    {
        $this->db->query('INSERT IGNORE INTO jtl_connector_product_checksum (endpoint_id, type, checksum) VALUES ("'.$endpointId.'",'.$type.',"'.$checksum.'")');
    }
}
