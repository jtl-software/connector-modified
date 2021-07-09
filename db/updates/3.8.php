<?php
/** @var \jtl\Connector\Core\Database\Mysql $db */
$db->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_tax_class` (
                `endpoint_id` INT(10) NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'
);
