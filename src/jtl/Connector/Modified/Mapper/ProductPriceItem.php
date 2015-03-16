<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class ProductPriceItem extends BaseMapper
{
    protected $mapperConfig = array(
        "getMethod" => "getItems",
        "mapPull" => array(
            "productPriceId" => null,
            "netPrice" => "personal_offer",
            "quantity" => "quantity"
        )
    );

    public function pull($data)
    {
        $return = [];

        $pricesData = $this->db->query("SELECT * FROM personal_offers_by_customers_status_".$data['customers_status_id']." WHERE products_id = ".$data['products_id']." && personal_offer > 0");

        foreach ($pricesData as $priceData) {
            $priceData['customers_status_id'] = $data['customers_status_id'];
            $return[] = $this->generateModel($priceData);
        }

        $defaultPrice = array(
            'products_id' => $data['products_id'],
            'customers_status_id' => $data['customers_status_id'],
            'personal_offer' => $data['default_price'],
            'quantity' => 0
        );

        $return[] = $this->generateModel($defaultPrice);

        return $return;
    }

    public function push($data)
    {
        $productId = $data->getProductId()->getEndpoint();

        $defaultSet = false;

        foreach ($data->getItems() as $price) {
            $obj = new \stdClass();

            if (is_null($data->getCustomerGroupId()->getEndpoint())) {
                if (!$defaultSet) {
                    $obj->products_price = $price->getNetprice();
                    $this->db->updateRow($obj, 'products', 'products_id', $productId);

                    $defaultSet = true;
                }
            } else {
                $obj->products_id = $productId;
                $obj->personal_offer = $price->getNetprice();
                $obj->quantity = $price->getQuantity();

                $this->db->insertRow($obj, 'personal_offers_by_customers_status_'.$data->getCustomerGroupId()->getEndpoint());
            }
        }
    }

    protected function productPriceId($data)
    {
        return $data['products_id'].'_'.$data['customers_status_id'];
    }
}
