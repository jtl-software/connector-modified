<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Mapper\IPrimaryKeyMapper;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Core\Logger\Logger;

class PrimaryKeyMapper implements IPrimaryKeyMapper
{
    protected $db;

    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    public function getHostId($endpointId, $type)
    {
        $dbResult = $this->db->query('SELECT hostId FROM jtl_connector_link WHERE endpointId = "'.$endpointId.'" AND type = '.$type);

        $hostId = (count($dbResult) > 0) ? $dbResult[0]['hostId'] : null;

        Logger::write(sprintf('Trying to get hostId with endpointId (%s) and type (%s) ... hostId: (%s)', $endpointId, $type, $hostId), Logger::DEBUG, 'linker');

        return $hostId;
    }

    public function getEndpointId($hostId, $type, $relationType = null)
    {
        $dbResult = $this->db->query('SELECT endpointId FROM jtl_connector_link WHERE hostId = '.$hostId.' AND type = '.$type);

        $endpointId = (count($dbResult) > 0) ? $dbResult[0]['endpointId'] : null;

        Logger::write(sprintf('Trying to get endpointId with hostId (%s) and type (%s) ... endpointId: (%s)', $hostId, $type, $endpointId), Logger::DEBUG, 'linker');

        return $endpointId;
    }

    public function save($endpointId, $hostId, $type)
    {
        Logger::write(sprintf('Save link with endpointId (%s), hostId (%s) and type (%s)', $endpointId, $hostId, $type), Logger::DEBUG, 'linker');

        $this->db->query('INSERT IGNORE INTO jtl_connector_link (endpointId, hostId, type) VALUES ("'.$endpointId.'",'.$hostId.','.$type.')');
    }

    public function delete($endpointId = null, $hostId = null, $type)
    {
        Logger::write(sprintf('Delete link with endpointId (%s), hostId (%s) and type (%s)', $endpointId, $hostId, $type), Logger::DEBUG, 'linker');

        $where = 'type = '.$type;

        if ($endpointId && $endpointId != '') {
            $where .= ' && endpointId = "'.$endpointId.'"';
        }

        if ($hostId) {
            $where .= ' && hostId = '.$hostId;
        }

        $this->db->query('DELETE FROM jtl_connector_link WHERE '.$where);
    }

    public function clear()
    {
        Logger::write('Clearing linking tables', Logger::DEBUG, 'linker');

        $this->db->query('TRUNCATE TABLE jtl_connector_link');

        return true;
    }

    public function gc()
    {
        /*
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN categories ON categories_id = endpointId WHERE type=1 && categories_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN customers ON customers_id = endpointId WHERE type=2 && customers_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN orders ON orders_id = endpointId WHERE type=4 && orders_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN products ON endpointId = CONCAT("pID_",products_id) WHERE type=16 && products_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN categories ON endpointId = CONCAT("cID_",categories_id) WHERE type=16 && categories_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN products_images ON image_id = endpointId WHERE type=16 && image_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN manufacturers ON manufacturers_id = endpointId WHERE type=32 && manufacturers_id IS NULL');
        $this->db->query('DELETE jtl_connector_link FROM jtl_connector_link LEFT JOIN products ON products_id = endpointId WHERE type=64 && products_id IS NULL');
        */
        return true;
    }
}
