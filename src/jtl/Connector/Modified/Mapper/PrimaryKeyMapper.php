<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Mapper\IPrimaryKeyMapper;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Core\Logger\Logger;

class PrimaryKeyMapper implements IPrimaryKeyMapper
{
    protected $db;

    protected static $types = array(
        1 => 'category',
        2 => 'customer',
        4 => 'customer_order',
        8 => 'delivery_note',
        16 => 'image',
        32 => 'manufacturer',
        64 => 'product',

        512 => 'payment',
        1024 => 'crossselling',
        2048 => 'crossselling_group'
    );

    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    public function getHostId($endpointId, $type)
    {
        if (isset(static::$types[$type])) {
            $dbResult = $this->db->query("SELECT host_id FROM jtl_connector_link_" . static::$types[$type] . " WHERE endpoint_id = '" . $endpointId . "'");
    
            $host_id = (count($dbResult) > 0) ? $dbResult[0]['host_id'] : null;
        
            Logger::write(sprintf('Trying to get host_id with endpoint_id (%s) and type (%s) ... host_id: (%s)', $endpointId, $type, $host_id), Logger::DEBUG, 'linker');
        
            return $host_id;
        }
    }

    public function getEndpointId($hostId, $type, $relationType = null)
    {
        if (isset(static::$types[$type])) {
            $dbResult = $this->db->query("SELECT endpoint_id FROM jtl_connector_link_".static::$types[$type]." WHERE host_id = ".$hostId);
    
            $endpoint_id = (count($dbResult) > 0) ? $dbResult[0]['endpoint_id'] : null;
    
            Logger::write(sprintf('Trying to get endpoint_id with host_id (%s) and type (%s) ... endpoint_id: (%s)', $hostId, $type, $endpoint_id), Logger::DEBUG, 'linker');
    
            return $endpoint_id;
        }
    }

    public function save($endpointId, $hostId, $type)
    {
        if (isset(static::$types[$type])) {
            Logger::write(sprintf('Save link with endpoint_id (%s), host_id (%s) and type (%s)', $endpointId, $hostId, $type), Logger::DEBUG, 'linker');
    
            $this->db->query("INSERT IGNORE INTO jtl_connector_link_".static::$types[$type]." (endpoint_id, host_id) VALUES ('".$endpointId."',".$hostId.")");
        }
    }

    public function delete($endpointId = null, $hostId = null, $type)
    {
        if (isset(static::$types[$type])) {
            Logger::write(sprintf('Delete link with endpoint_id (%s), host_id (%s) and type (%s)', $endpointId, $hostId, $type), Logger::DEBUG, 'linker');
    
            $where = [];
    
            if ($endpointId && $endpointId != '') {
                $where[] = 'endpoint_id = "'.$endpointId.'"';
            }
    
            if ($hostId) {
                $where[] = 'host_id = '.$hostId;
            }
    
            if (!empty($where)) {
                $this->db->query('DELETE FROM jtl_connector_link_' . static::$types[$type] . ' WHERE ' . implode(' AND ', $where));
            }
        }
    }

    public function clear()
    {
        Logger::write('Clearing linking tables', Logger::DEBUG, 'linker');

        foreach (static::$types as $id => $name) {
            $this->db->query('TRUNCATE TABLE jtl_connector_link_'.$name);
        }

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
