<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\DataModel;

class ProductSpecialPriceItem extends AbstractMapper
{
    protected $mapperConfig = [
        "mapPull" => [
            "customerGroupId" => null,
            "productSpecialPriceId" => "specials_id",
            "priceNet" => "specials_new_products_price"
        ]
    ];

    public function pull($data = null, $limit = null): array
    {
        return [$this->generateModel($data)];
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $prices = $model->getItems();
        $dbObj->specials_new_products_price = $prices[0]->getPriceNet();
    }

    protected function customerGroupId($data)
    {
        return $this->shopConfig['settings']['DEFAULT_CUSTOMERS_STATUS_ID'];
    }
}
