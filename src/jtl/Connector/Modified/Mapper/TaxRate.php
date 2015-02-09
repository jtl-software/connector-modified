<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class TaxRate extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "tax_rates",
        "mapPull" => array(
            "id" => "tax_rates_id",
            "taxZoneId" => "tax_zone_id",
            "taxClassId" => "tax_class_id",
            "rate" => "tax_rate",
            "priority" => "tax_priority",
        ),
    );
}
