<?php
namespace jtl\Connector\Modified\Mapper;

class ProductSpecialPriceItem extends BaseMapper
{
    protected $mapperConfig = array(
        "mapPull" => array(
            "customerGroupId" => null,
            "productSpecialPriceId" => "specials_id",
            "priceNet" => "specials_new_products_price"
        )
    );

    public function pull($data = null, $limit = null)
    {
        return array($this->generateModel($data));
    }

    public function push($parent, $dbObj = null)
    {
        $prices = $parent->getItems();
        $dbObj->specials_new_products_price = $prices[0]->getPriceNet();
    }

    protected function customerGroupId($data)
    {
        return $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID'];
    }
}
