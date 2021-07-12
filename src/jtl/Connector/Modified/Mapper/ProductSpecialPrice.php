<?php
namespace jtl\Connector\Modified\Mapper;

class ProductSpecialPrice extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "specials",
        "query" => "SELECT * FROM specials WHERE products_id=[[products_id]]",
        "getMethod" => "getSpecialPrices",
        "where" => "specials_id",
        "mapPull" => [
            "id" => "specials_id",
            "productId" => "products_id",
            "isActive" => "status",
            "activeUntilDate" => null,
            "considerDateLimit" => null,
            "items" => "ProductSpecialPriceItem|addItem"
        ],
        "mapPush" => [
            "specials_id" => "id",
            "products_id" => "productId",
            "status" => "isActive",
            "expires_date" => "activeUntilDate",
            "ProductSpecialPriceItem|addItem|true" => "items"
        ]
    ];

    protected function considerDateLimit($data)
    {
        return $data['expires_date'] == '0000-00-00 00:00:00' ? false : true;
    }

    protected function activeUntilDate($data)
    {
        return $data['expires_date'] == '0000-00-00 00:00:00' ? null : $data['expires_date'];
    }

    public function push($parent, $dbObj = null)
    {
        $id = $parent->getId()->getEndpoint();

        if (!is_null($parent->getSpecialPrices())) {
            foreach ($parent->getSpecialPrices() as $special) {
                $special->setProductId($parent->getId());
            }

            return parent::push($parent, $dbObj);
        }
    }
}
