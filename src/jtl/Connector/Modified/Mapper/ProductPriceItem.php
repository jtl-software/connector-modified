<?php
namespace jtl\Connector\Modified\Mapper;

class ProductPriceItem extends BaseMapper
{
    protected $mapperConfig = array(
        "getMethod" => "getItems",
        "mapPull" => array(
            "productPriceId" => null,
            "netPrice" => null,
            "quantity" => null
        )
    );

    public function pull($data = null, $limit = null)
    {
        $return = [];

        $pricesData = $this->db->query("SELECT * FROM personal_offers_by_customers_status_".$data['customers_status_id']." WHERE products_id = ".$data['products_id']." && personal_offer > 0");

        foreach ($pricesData as $priceData) {
            $priceData['customers_status_id'] = $data['customers_status_id'];

            $return[] = $this->generateModel($priceData);

        }

        return $return;
    }

    public function push($data, $dbObj = null)
    {
        $productId = $data->getProductId()->getEndpoint();

        foreach ($data->getItems() as $price) {
            $obj = new \stdClass();

            if (is_null($data->getCustomerGroupId()->getEndpoint()) || $data->getCustomerGroupId()->getEndpoint() == '') {
                if (Product::isVarCombi($productId)){
                    $productId = Product::extractOptionValueId($productId);
                    $newPrice = $this->db->query(sprintf("SELECT products_price FROM products WHERE products_id = (SELECT products_id FROM products_attributes WHERE options_values_id = %s)", $productId));
    
                    $newPrice = $price->getNetPrice() - $newPrice[0]['products_price'];
                    if ($newPrice >= 0) {
                        $pricePrefix = "+";
                    } else {
                        $pricePrefix = "-";
                        $newPrice = $newPrice * -1;
                    }
                    
                    $obj->price_prefix = $pricePrefix;
                    $obj->options_values_price = $newPrice;
                    $this->db->updateRow($obj, 'products_attributes', 'options_values_id', $productId);
                } else {
                    $obj->products_price = $price->getNetPrice();
                    $this->db->updateRow($obj, 'products', 'products_id', $productId);
                }
            } else {
                    $obj->products_id = $productId;
                    $obj->personal_offer = $price->getNetprice();
                    $obj->quantity = ($price->getQuantity() == 0) ? 1 : $price->getQuantity();
    
                    $this->db->insertRow($obj, 'personal_offers_by_customers_status_'.$data->getCustomerGroupId()->getEndpoint());
            }
        }

        return $data;
    }

    protected function quantity($data)
    {
        return $data['quantity'] == 1 ? 0 : $data['quantity'];
    }

    protected function netPrice($data)
    {
        return floatval($data['personal_offer']);
    }

    protected function productPriceId($data)
    {
        return $data['products_id'].'_'.$data['customers_status_id'];
    }
}
