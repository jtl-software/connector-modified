<?php
/** @var \jtl\Connector\Core\Database\Mysql $db */
$db->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_tax_class` (
                `endpoint_id` INT NOT NULL,
                `host_id` INT unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'
);
