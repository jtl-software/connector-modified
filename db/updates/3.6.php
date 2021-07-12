<?php
/** @var \jtl\Connector\Core\Database\Mysql $db */
$db->query('ALTER TABLE jtl_connector_product_checksum MODIFY endpoint_id VARCHAR(20)');
