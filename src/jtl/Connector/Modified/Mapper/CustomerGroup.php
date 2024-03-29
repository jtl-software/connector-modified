<?php
namespace jtl\Connector\Modified\Mapper;

class CustomerGroup extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "customers_status",
        "query" => "SELECT customers_status_id, customers_status_discount, customers_status_add_tax_ot 
             FROM customers_status  
             GROUP BY customers_status_id, customers_status_discount, customers_status_add_tax_ot",
        "identity" => "getId",
        "getMethod" => "getCustomerGroups",
        "mapPull" => [
            "id" => "customers_status_id",
            "discount" => "customers_status_discount",
            "applyNetPrice" => "customers_status_add_tax_ot",
            "isDefault" => null,
            "i18ns" => "CustomerGroupI18n|addI18n",
            "attributes" => "CustomerGroupAttr|addAttribute"
        ],
        "mapPush" => [
            "CustomerGroupI18n|addI18n" => "i18ns"
        ]
    ];

    protected function isDefault($data)
    {
        return ($data['customers_status_id'] == $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID']) ? true : false;
    }
}
