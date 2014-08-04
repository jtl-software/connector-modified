<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrder extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders",
        "mapPull" => array(
        	"id" => "orders_id",
        	"customerId" => "customers_id",
        	"created" => "date_purchased",
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
            "orders_id" => "_id",
            "customers_id" => "_customerId",
            "date_purchased" => "_created",
            "comments" => "_note",
            "orders_status" => "_status",
            "customers_ip" => "_ip",
            "shipping_class" => "_shippingMethodCode",
            "shipping_method" => "_shippingMethodName",
            "payment_method" => null,
            "orders_status" => null,
            "currency" => "_currencyIso"            
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
    
    /*
    protected function orders_status($data) {
        $sqlite = new \PDO('sqlite:'.realpath(__DIR__.'/../../XTC/').'/xtc.sdb');
    
        $xtcStatus = $sqlite->query('SELECT xtc FROM status WHERE jtl="'.$data->_status.'"');
        $xtcStatus = $xtcStatus->fetchColumn();
    
        return $xtcStatus;
    }
    */
    protected function shippingAddressId($data) {
    	return 'cID_'.$data['customers_id'];
    }
    
    protected function billingAddressId($data) {
    	return 'cID_'.$data['customers_id'];
    }
    
    protected function paymentModuleCode($data) {
        return $this->paymentMapping[$data['payment_method']];
    }     
}