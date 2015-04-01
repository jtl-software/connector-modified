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

    public function getEndpointId($hostId, $type)
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

        if ($endpointId) {
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
}
