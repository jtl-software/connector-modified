<?php

$db->query('CREATE TABLE IF NOT EXISTS jtl_connector_link_products_option (
  endpoint_id varchar(255) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');