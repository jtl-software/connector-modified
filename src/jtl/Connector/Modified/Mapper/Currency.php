<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class Currency extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "currencies",
        "where" => "currencies_id",
        "identity" => "getId",
        "getMethod" => "getCurrencies",
        "mapPull" => array(
            "id" => "currencies_id",
            "name" => "title",
            "factor" => "value",
            "delimiterCent" => "decimal_point",
            "delimiterThousand" => "thousands_point",
            "isDefault" => null
        ),
        "mapPush" => array(
            "currencies_id" => "id",
            "title" => "name",
            "value" => "factor",
            "decimal_point" => "delimiterCent",
            "thousands_point" => "delimiterThousand",
            "code" => null,
            "decimal_places" => null,
            "symbol_right" => null
        )
    );

    protected function isDefault($data)
    {
        return $data['code'] == $this->shopConfig['settings']['DEFAULT_CURRENCY'] ? true : false;
    }

    protected function code($data)
    {
        if ($data->getIsDefault() === true) {
            $this->db->query('UPDATE configuration SET configuration_value="'.$data->getIso().'" WHERE configuration_key="DEFAULT_CURRENCY"');
        }

        return $data->getIso();
    }
    
    protected function decimal_places($data)
    {
        return 2;
    }

    protected function symbol_right($data)
    {
        return $data->getIso();
    }
}
