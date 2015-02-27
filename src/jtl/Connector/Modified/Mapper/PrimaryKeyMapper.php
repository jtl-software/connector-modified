<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Mapper\IPrimaryKeyMapper;
use jtl\Connector\Core\Database\Mysql;

class PrimaryKeyMapper implements IPrimaryKeyMapper
{
    protected $db;

    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    public function getHostId($endpointId, $type)
    {
        $dbResult = $this->db->query('SELECT hostId FROM jtl_connector_link WHERE endpointId = '.$endpointId.' AND type = '.$type);

        return (count($dbResult) > 0) ? $dbResult[0]['hostId'] : null;
    }

    public function getEndpointId($hostId, $type)
    {
        $dbResult = $this->db->query('SELECT endpointId FROM jtl_connector_link WHERE hostId = '.$hostId.' AND type = '.$type);

        return (count($dbResult) > 0) ? $dbResult[0]['endpointId'] : null;
    }

    public function save($endpointId, $hostId, $type)
    {
        $this->db->query('INSERT IGNORE INTO jtl_connector_link (endpointId, hostId, type) VALUES ('.$endpointId.','.$hostId.','.$type.')');
    }

    public function delete($endpointId = null, $hostId = null, $type)
    {
        $where = 'type = '.$type;

        if ($endpointId) {
            $where .= ' && endpointId = '.$endpointId;
        }

        if ($hostId) {
            $where .= ' && hostId = '.$hostId;
        }

        $this->db->query('DELETE FROM jtl_connector_link WHERE '.$where);
    }

    public function clear()
    {
        $this->db->query('TRUNCATE TABLE jtl_connector_link');
    }
}
