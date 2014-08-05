<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerGroup extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers_status",
        "query" => "SELECT * FROM customers_status GROUP BY customers_status_id",
        "mapPull" => array(
            "id" => "customers_status_id",
            "discount" => "customers_status_discount",
            "isDefault" => null,
            "i18n" => "CustomerGroupI18n|addI18n"
        )
    );   

    protected function isDefault($data) {
        return ($data['customers_status_id'] == $this->shopConfig['DEFAULT_CUSTOMERS_STATUS_ID']) ? true : false;
    }
}