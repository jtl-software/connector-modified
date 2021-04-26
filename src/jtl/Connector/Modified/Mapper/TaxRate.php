<?php
namespace jtl\Connector\Modified\Mapper;

class TaxRate extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "tax_rates",
        "query" => "SELECT tax_rate FROM tax_rates GROUP BY tax_rate",
        "mapPull" => [
            //"id" => "tax_rates_id",
            "rate" => "tax_rate"
        ]
    ];
}
