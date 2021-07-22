<?php
namespace jtl\Connector\Modified\Mapper;

class Unit extends AbstractMapper
{
    protected $mapperConfig = [
        "query" => "SELECT products_vpe_id FROM products_vpe GROUP BY products_vpe_id",
        "table" => "products_vpe",
        "where" => "products_vpe_id",
        "getMethod" => "getUnits",
        "identity" => "getId",
        "mapPull" => [
            "id" => "products_vpe_id",
            "i18ns" => "UnitI18n|addI18n"
        ],
        "mapPush" => [
            "UnitI18n|addI18n" => "i18ns",
        ]
    ];
    
    /**
     * @todo REMOVE THIS FUNCTION AFTER UNITS ARE FIXED!
     * @param null $parentData
     * @param null $limit
     * @return array
     */
    public function pull($parentData = null, $limit = null): array
    {
        return [];
    }
}
