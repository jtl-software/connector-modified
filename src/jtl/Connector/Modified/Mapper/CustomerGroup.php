<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerGroup extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers_status",
        "query" => "SELECT * FROM customers_status GROUP BY customers_status_id",
        "identity" => "getId",
        "getMethod" => "getCustomerGroups",
        "mapPull" => array(
            "id" => "customers_status_id",
            "discount" => "customers_status_discount",
            "applyNetPrice" => "customers_status_add_tax_ot",
            "isDefault" => null,
            "i18ns" => "CustomerGroupI18n|addI18n"
        ),
        "mapPush" => array(
            "CustomerGroupI18n|addI18n" => "i18ns"
        )
    );

    protected function isDefault($data)
    {
        return ($data['customers_status_id'] == $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID']) ? true : false;
    }
}
