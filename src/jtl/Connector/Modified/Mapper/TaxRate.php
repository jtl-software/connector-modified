<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class TaxRate extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "tax_rates",
        "query" => "SELECT * FROM tax_rates WHERE tax_rate > 0",
        "mapPull" => array(
            "id" => "tax_rates_id",
            "rate" => "tax_rate"            
        )
    );
}
