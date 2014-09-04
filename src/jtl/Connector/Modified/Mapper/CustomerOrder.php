<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrder extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders",
        "where" => "orders_id",
        "mapPull" => array(
        	"id" => "orders_id",
            "orderNumber" => "orders_id",
        	"customerId" => "customers_id",
        	"created" => "date_purchased",
            "note" => "comments",
        	"status" => null,
        	"paymentModuleCode" => null,
        	"currencyIso" => "currency",
        	"shippingAddressId" => null,
        	"billingAddressId" => null,
            "billingAddress" => "CustomerOrderBillingAddress|addBillingAddress",
            "shippingAddress" => "CustomerOrderShippingAddress|addShippingAddress",
            "items" => "CustomerOrderItem|addItem"
        ),
        "mapPush" => array(
            "orders_id" => "id",                
            "customers_id" => "customerId",
            "date_purchased" => "created",
            "comments" => "note",
            "orders_status" => null,
            "payment_method" => null,
            "currency" => "currencyIso",
            "CustomerOrderBillingAddress|addBillingAddress|true" => "billingAddress",
            //"CustomerOrderShippingAddress|addShippingAddress" => "shippingAddress"
        )
    );

    private $paymentMapping = array(
        'cash' => 'pm_cash',
        'klarna_SpecCamp' => 'pm_klarna',
        'klarna_invoice' => 'pm_klarna',
        'klarna_partPayment' => 'pm_klarna',
        'banktransfer' => 'pm_direct_debit',
        'cod' => 'pm_cash_on_delivery',
        'paypal' => 'pm_paypal_standard',
        'paypal_ipn' => 'pm_paypal_standard',
        'paypalexpress' => 'pm_paypal_express',
        'amoneybookers' => 'pm_skrill_acc',
        'moneybookers_giropay' => 'pm_skrill_gir',
        'moneybookers_ideal' => 'pm_skrill_idl',
        'moneybookers_mae' => 'pm_skrill_mae',
        'moneybookers_netpay' => 'pm_skrill_npy',
        'moneybookers_psp' => 'pm_skrill_psp',
        'moneybookers_pwy' => 'pm_skrill_pwy',
        'moneybookers_sft' => 'pm_skrill_sft',
        'moneybookers_wlt' => 'pm_skrill_wlt',
        'invoice' => 'pm_invoice',
        'pn_sofortueberweisung' => 'pm_sofort',
        'worldpay' => 'pm_worldpay'
    );
    
    public function pull($params) {
        if(isset($params->from) && isset($params->until)) {
    	    $from = DateUtil::map($params->from,\DateTime::ISO8601,'Y-m-d H:i:s');
    	    $until = DateUtil::map($params->until,\DateTime::ISO8601,'Y-m-d H:i:s');
    	    $where = 'WHERE last_modified >= "'.$from.'" && last_modified <= "'.$until.'" ';
    	}
    	else $where = '';
    	
        $this->mapperConfig['query'] = 'SELECT * FROM orders '.$where;
        
        return parent::pull(null,$params->offset,$params->limit);        
    }
    
    protected function status($data) {
        $sqlite = $this->getSqlite();
        
        $jtlStatus = $sqlite->query('SELECT jtl FROM status WHERE modified="'.$data['orders_status'].'"');
        $jtlStatus = $jtlStatus->fetchColumn();
        
        return $jtlStatus;
    }
    
    protected function orders_status($data) {
        $sqlite = $this->getSqlite();
    
        $modifiedStatus = $sqlite->query('SELECT modified FROM status WHERE jtl="'.$data->getStatus().'"');
        $modifiedStatus = $modifiedStatus->fetchColumn();
    
        return $modifiedStatus;
    }
    
    protected function shippingAddressId($data) {
    	return 'cID_'.$data['customers_id'];
    }
    
    protected function billingAddressId($data) {
    	return 'cID_'.$data['customers_id'];
    }
    
    protected function paymentModuleCode($data) {
        return $this->paymentMapping[$data['payment_method']];
    }     
    
    protected function payment_method($data) {
        $payments = array_flip($this->paymentMapping);
        
        return $payments[$data->getPaymentModuleCode()];
    }
}