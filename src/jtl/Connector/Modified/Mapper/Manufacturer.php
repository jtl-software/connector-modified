<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class Manufacturer extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "manufacturers",
        "where" => "manufacturers_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "manufacturers_id",
            "i18ns" => "ManufacturerI18n|addI18n",
        ),
        "mapPush" => array(
            "manufacturers_id" => "id",
            "ManufacturerI18n|addI18n|true" => "i18ns",
        ),
    );
}
