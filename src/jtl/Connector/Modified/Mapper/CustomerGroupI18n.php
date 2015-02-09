<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerGroupI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers_status",
        "getMethod" => "getI18ns",
        "query" => "SELECT customers_status.customers_status_id,customers_status.customers_status_name,languages.code FROM customers_status LEFT JOIN languages ON languages.languages_id=customers_status.language_id WHERE customers_status.customers_status_id=[[customers_status_id]]",
        "mapPull" => array(
            "customerGroupId" => "customers_status_id",
            "name" => null,
            "languageISO" => null,
        ),
    );

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function name($data)
    {
        return html_entity_decode($data['customers_status_name']);
    }
}
