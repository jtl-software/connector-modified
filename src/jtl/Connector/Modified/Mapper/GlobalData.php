<?php
namespace jtl\Connector\Modified\Mapper;

class GlobalData extends \jtl\Connector\Modified\Mapper\AbstractMapper
{
    protected $mapperConfig = [
        "mapPull" => [
            "languages" => "Language|addLanguage",
            "customerGroups" => "CustomerGroup|addCustomerGroup",
            "taxRates" => "TaxRate|addTaxRate",
            "currencies" => "Currency|addCurrency",
            "units" => "Unit|addUnit",
            "crossSellingGroups" => "CrossSellingGroup|addCrossSellingGroup",
            "shippingMethods" => "ShippingMethod|addShippingMethod"
        ],
        "mapPush" => [
            "Currency|addCurrency" => "currencies",
            "Unit|addUnit" => "units",
            "CrossSellingGroup|addCrossSellingGroup" => "crossSellingGroups",
            "CustomerGroup|addCustomerGroup" => "customerGroups"
        ]
    ];

    public function pull($parentData = null, $limit = null): array
    {
        $globalData = $this->generateModel([]);

        return [$globalData];
    }
}
