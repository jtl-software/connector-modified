<?php
$types = array(
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

$queryInt = 'CREATE TABLE IF NOT EXISTS %s (
  endpoint_id INT(10) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

$queryChar = 'CREATE TABLE IF NOT EXISTS %s (
  endpoint_id varchar(255) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

foreach($types as $id => $name) {
    if ($id == 16 || $id == 64) {
        $db->query(sprintf($queryChar, 'jtl_connector_link_'.$name));
    } else {
        $db->query(sprintf($queryInt, 'jtl_connector_link_'.$name));
    }
}

$existingTypes = $db->query('SELECT type FROM jtl_connector_link GROUP BY type');

foreach ($existingTypes as $existingType) {
    $typeId = (int) $existingType['type'];
    $tableName = 'jtl_connector_link_'.$types[$typeId];
    $db->query("INSERT INTO {$tableName} (host_id, endpoint_id) 
      SELECT hostId, endpointId FROM jtl_connector_link WHERE type = {$typeId}
    ");
}

$db->query("RENAME TABLE jtl_connector_link TO jtl_connector_link_backup");
$db->query("ALTER TABLE jtl_connector_product_checksum MODIFY endpoint_id VARCHAR(10)");

file_put_contents(CONNECTOR_DIR.'/db/version', $updateFile->getBasename('.php'));
