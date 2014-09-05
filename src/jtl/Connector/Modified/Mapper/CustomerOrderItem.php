<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

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
        	"productId" => "products_id",
        	"customerOrderId" => "orders_id",
        	"quantity" => "products_quantity",
        	"name" => "products_name",
        	"price" => null,
        	"vat" => "products_tax",
        	"sku" => "products_model",
            "variations" => "CustomerOrderItemVariation|addVariation",
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
    
    public function push($parent,$dbObj) {
        $return = [];
        
        $shippingCosts = 0;
        
        foreach($parent->getItems() as $itemData) {
            if($itemData->getType() == "product") $return[] = $this->generateDbObj($itemData,$dbObj,$parent);
            elseif($itemData->getType() == "shipping") $shippingCosts += $itemData->getPrice();
        }
        
        $ot_shipping = new \stdClass();
        $ot_shipping->title = '<b>Summe</b>:';
        $ot_shipping->text = '<b> '.number_format($shippingCosts,2,',','.').' '.$parent->getCurrencyIso().'</b>';
        $ot_shipping->value = $shippingCosts;
        $ot_shipping->sort_order = 30;
        $ot_shipping->class = 'ot_shipping';
        $ot_shipping->orders_id = $parent->getId()->getEndpoint();
        
        /*
        $totals = [];
        
        $ot_subtotal = new \stdClass();
        $ot_subtotal->title = 'Zwischensumme:';
        $ot_subtotal->text = '<b> '.number_format($sum,2,',','.').' '.$order->_currencyIso.'</b>';
        $ot_subtotal->value = $sum;
        $ot_subtotal->sort_order = 10;
        $ot_subtotal->class = 'ot_subtotal';
        $totals[] = $ot_subtotal;
        
        $ot_total = new \stdClass();
        $ot_total->title = '<b>Summe</b>:';
        $ot_total->text = number_format($dd,2,',','.').' '.$order->_currencyIso;
        $ot_total->value = $sum + $ot_shipping->value;
        $ot_total->sort_order = 99;
        $ot_total->class = 'ot_total';
        $totals[] = $ot_total;
        
        $ot_tax = new \stdClass();
        $ot_tax->title = '<b>Steuer</b>:';
        $ot_tax->text = '<b> '.number_format($sum,2,',','.').' '.$order->_currencyIso.'</b>';
        $ot_tax->value = $sum;
        $ot_tax->sort_order = 30;
        $ot_tax->class = 'ot_tax';
        $totals[] = $ot_tax;
        
        foreach($totals as $total) {
            $this->db->deleteInsertRow($total,'orders_total',array('orders_id','class'),array($data->getId()->getEndpoint(),$total->class));
        }
        */
                
        return $return;
    }
    
    protected function price($data) {
        return ($data['products_price']/(100+$data['products_tax'])) * 100;
    }
    
    protected function products_price($data) {
        return ($data->getPrice() / 100) * (100 + $data->getVat());
    }
    
    protected function final_price($data) {
        return (($data->getPrice() / 100) * (100 + $data->getVat())) * $data->getQuantity();
    }
    
    protected function allow_tax($data) {
        return 1;
    }      

    protected function orders_id($data,$model,$parent) {
        $data->setCustomerOrderId($parent->getId());
        
        return $parent->getId()->getEndpoint();
    }
    
    protected function type($data) {
        return 'product';
    }
}