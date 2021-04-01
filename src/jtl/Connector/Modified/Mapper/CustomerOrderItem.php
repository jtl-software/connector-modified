<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Logger\Logger;

class CustomerOrderItem extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders_products",
        "query" => "SELECT * FROM orders_products WHERE orders_id=[[orders_id]]",
        "where" => "orders_products_id",
        "getMethod" => "getItems",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "orders_products_id",
            "productId" => null,
            "customerOrderId" => "orders_id",
            "quantity" => "products_quantity",
            "name" => null,
            "price" => null,
            "vat" => "products_tax",
            "sku" => null,
            "type" => null
        ),
        "mapPush" => array(
            "orders_products_id" => "id",
            "products_id" => "productId",
            "orders_id" => null,
            "products_quantity" => "quantity",
            "products_name" => "name",
            "products_price" => null,
            "products_tax" => "vat",
            "products_model" => "sku",
            "allow_tax" => null,
            "final_price" => null,
            "CustomerOrderItemVariation|addVariation" => "variations"
        )
    );

    public function push($parent, $dbObj = null)
    {
        $return = [];

        $shippingCosts = 0;
        $sum = 0;
        $taxes = 0;

        foreach ($parent->getItems() as $itemData) {
            if ($itemData->getType() == "product") {
                $return[] = $this->generateDbObj($itemData, $dbObj, $parent);
                $tax = ($itemData->getPrice() / 100) * $itemData->getVat();
                $taxes += $tax;
                $sum += $itemData->getPrice() + $tax;
            } elseif ($itemData->getType() == "shipping") {
                $shippingCosts += $itemData->getPrice();
                $tax = ($itemData->getPrice() / 100) * $itemData->getVat();
                $taxes += $tax;
            }
        }

        $totals = [];

        $ot_shipping = new \stdClass();
        $ot_shipping->title = $parent->getShippingMethodName().':';
        $ot_shipping->text = number_format($shippingCosts, 2, ',', '.').' '.$parent->getCurrencyIso();
        $ot_shipping->value = $shippingCosts;
        $ot_shipping->sort_order = 30;
        $ot_shipping->class = 'ot_shipping';
        $totals[] = $ot_shipping;

        $ot_subtotal = new \stdClass();
        $ot_subtotal->title = 'Zwischensumme:';
        $ot_subtotal->text = number_format($sum, 2, ',', '.').' '.$parent->getCurrencyIso();
        $ot_subtotal->value = $sum;
        $ot_subtotal->sort_order = 10;
        $ot_subtotal->class = 'ot_subtotal';
        $totals[] = $ot_subtotal;

        $ot_total = new \stdClass();
        $ot_total->title = '<b>Summe</b>:';
        $ot_total->text = '<b> '.number_format($sum+$shippingCosts, 2, ',', '.').' '.$parent->getCurrencyIso().'</b>';
        $ot_total->value = $sum + $shippingCosts;
        $ot_total->sort_order = 99;
        $ot_total->class = 'ot_total';
        $totals[] = $ot_total;

        $ot_tax = new \stdClass();
        $ot_tax->title = 'Steuer:';
        $ot_tax->text = number_format($taxes, 2, ',', '.').' '.$parent->getCurrencyIso();
        $ot_tax->value = $taxes;
        $ot_tax->sort_order = 30;
        $ot_tax->class = 'ot_tax';
        $totals[] = $ot_tax;

        foreach ($totals as $total) {
            $total->orders_id = $parent->getId()->getEndpoint();
            $this->db->deleteInsertRow($total, 'orders_total', array('orders_id', 'class'), array($parent->getId()->getEndpoint(), $total->class));
        }

        return $return;
    }
    
    protected function sku($data)
    {
        $attributeData = $this->db->query(
            sprintf("SELECT * FROM orders_products_attributes WHERE orders_id = %s AND orders_products_id = %s",
                $data['orders_id'], $data['orders_products_id']
            )
        );
        
        if (isset($attributeData[0]['attributes_model'])){
            return $attributeData[0]['attributes_model'];
        } else {
            return $data['products_model'];
        }
    }
    
    protected function price($data)
    {
        if ($data['allow_tax'] == "0") {
            return $data['products_price'];
        } else {
            return ($data['products_price'] / (100 + $data['products_tax'])) * 100;
        }
    }

    protected function products_price($data)
    {
        return ($data->getPrice() / 100) * (100 + $data->getVat());
    }

    protected function final_price($data)
    {
        return (($data->getPrice() / 100) * (100 + $data->getVat())) * $data->getQuantity();
    }

    protected function allow_tax($data)
    {
        return 1;
    }

    protected function orders_id($data, $model, $parent)
    {
        $data->setCustomerOrderId($parent->getId());

        return $parent->getId()->getEndpoint();
    }

    /**
     * @param array $data
     * @return string
     */
    protected function type(array $data): string
    {
        return 'product';
    }

    /**
     * @param array $data
     * @return string
     */
    protected function productId(array $data): string
    {
        $productId = $data['products_id'];
        
        $combiId = $this->db->query(sprintf('SELECT products_attributes_id FROM products_attributes WHERE attributes_model = \'%s\'', $data['attributes_model']));
        if (is_array($combiId) && count($combiId) === 1){
            return Product::createProductEndpoint($productId, $combiId[0]['products_attributes_id']);
        }
        
        return $productId;
    }

    /**
     * @param $data
     * @return string
     */
    protected function name(array $data): string
    {
        $productOptionsValues = $this->db->query(
            sprintf("SELECT products_options_values FROM orders_products_attributes WHERE orders_id = %s AND orders_products_id = %s", $data['orders_id'], $data['orders_products_id'])
        );

        if (empty($productOptionsValues) || !is_array($productOptionsValues)){
            return $data['products_name'];
        }

        $productOptionsValues = array_map(function(array $data) {
            return $data['products_options_values'];
        }, $productOptionsValues);

        return sprintf('%s %s', $data['products_name'], implode(' / ', $productOptionsValues));
    }
}
