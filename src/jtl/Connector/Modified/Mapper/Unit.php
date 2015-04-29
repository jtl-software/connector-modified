<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class Unit extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT products_vpe_id FROM products_vpe GROUP BY products_vpe_id",
        "table" => "products_vpe",
        "mapPull" => array(
            "id" => "products_vpe_id",
            "i18ns" => "UnitI18n|addI18n"
        )
    );
}
