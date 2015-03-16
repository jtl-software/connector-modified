<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;

class ProductPrice extends BaseMapper
{
    protected $mapperConfig = array(
        "getMethod" => "getPrices",
        "mapPull" => array(
            "id" => null,
            "customerGroupId" => "customers_status_id",
            "productId" => "products_id",
            "items" => "ProductPriceItem|addItem"
        ),
        "mapPush" => array(
            "ProductPriceItem|addItem" => "items"
        )
    );

    public function pull($data)
    {
        $customerGroups = $this->getCustomerGroups();

        $return = [];

        foreach ($customerGroups as $groupData) {
            $groupData['products_id'] = $data['products_id'];
            $groupData['default_price'] = $data['products_price'];

            $return[] = $this->generateModel($groupData);
        }

        /*
        $default = new ProductPriceModel();
        $default->setId($this->identity($data['products_id'].'_default'));
        $default->setProductId($this->identity($data['products_id']));

        $defaultItem = new ProductPriceItemModel();
        $defaultItem->setProductPriceId($default->getId());
        //$defaultItem->setQuantity(0);
        $defaultItem->setNetPrice(floatval($data['products_price']));

        $default->addItem($defaultItem);

        $return[] = $default;
        */

        return $return;
    }

    public function push($parent, $dbObj)
    {
        $productId = $parent->getId()->getEndpoint();

        if (!empty($productId)) {
            foreach ($this->getCustomerGroups() as $group) {
                $this->db->query('DELETE FROM personal_offers_by_customers_status_'.$group['customers_status_id'].' WHERE products_id='.$productId);
            }
        }

        return parent::push($parent, $dbObj);
    }

    protected function id($data)
    {
        return $data['products_id'].'_'.$data['customers_status_id'];
    }
}
