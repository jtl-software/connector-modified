<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Model\GlobalData as GlobalDataModel;

class GlobalData extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "mapPull" => array(
            "languages" => "Language|addLanguage",
            "customerGroups" => "CustomerGroup|addCustomerGroup",
            "taxRates" => "TaxRate|addTaxRate",
            "currencies" => "Currency|addCurrency",
            "units" => "Unit|addUnit"
        ),
        "mapPush" => array(
            "Currency|addCurrency" => "currencies",
            "Unit|addUnit" => "units"
        )
    );

    public function pull($parentData = null, $limit = null)
    {
        $globalData = $this->generateModel(null);

        return [$globalData];
    }
}
