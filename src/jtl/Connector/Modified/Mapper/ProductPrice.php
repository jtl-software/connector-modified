<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;

class ProductPrice extends AbstractMapper
{
    protected $mapperConfig = [
        "getMethod" => "getPrices",
        "mapPull" => [
            "id" => null,
            "customerGroupId" => "customers_status_id",
            "productId" => "products_id",
            "items" => "ProductPriceItem|addItem"
        ],
        "mapPush" => [
            "ProductPriceItem|addItem" => "items"
        ]
    ];

    public function pull($data = null, $limit = null): array
    {
        $customerGroups = $this->getCustomerGroups();

        $return = [];

        foreach ($customerGroups as $groupData) {
            $groupData['products_id'] = $data['products_id'];

            $price = $this->generateModel($groupData);

            if (count($price->getItems()) > 0) {
                $return[] = $price;
            }
        }

        $default = new ProductPriceModel();
        $default->setId($this->identity($data['products_id'].'_default'));
        $default->setProductId($this->identity($data['products_id']));
        $default->setCustomerGroupId($this->identity(''));

        $defaultItem = new ProductPriceItemModel();
        $defaultItem->setProductPriceId($default->getId());
        $defaultItem->setNetPrice(floatval($data['products_price']));

        $default->addItem($defaultItem);

        $return[] = $default;

        return $return;
    }

    public function push($parent, $dbObj = null)
    {
        if (get_class($parent) == 'jtl\Connector\Model\Product') {
            $productId = $parent->getId();

            foreach ($parent->getPrices() as $price) {
                $price->setProductId($productId);
            }
        } else {
            $customerGrp = $parent->getCustomerGroupId()->getEndpoint();
            $endpoint = $parent->getProductId()->getEndpoint();
            if (!is_null($customerGrp) && $customerGrp != '' && strpos($endpoint, '_') === false) {
                $this->db->query(sprintf('DELETE FROM personal_offers_by_customers_status_%d WHERE products_id = %d', $customerGrp, $endpoint));
            }

            unset($this->mapperConfig['getMethod']);
        }

        return parent::push($parent, $dbObj);
    }

    protected function id($data)
    {
        return $data['products_id'].'_'.$data['customers_status_id'];
    }
}
