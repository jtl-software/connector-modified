<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;

class CustomerOrder extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders",
        "query" => "SELECT o.* FROM orders o
            LEFT JOIN jtl_connector_link l ON o.orders_id = l.endpointId AND l.type = 4
            WHERE l.hostId IS NULL",
        "where" => "orders_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "orders_id",
            "orderNumber" => "orders_id",
            "customerId" => "customers_id",
            "creationDate" => "date_purchased",
            "note" => "comments",
            "paymentModuleCode" => null,
            "currencyIso" => "currency",
            "billingAddress" => "CustomerOrderBillingAddress|setBillingAddress",
            "shippingAddress" => "CustomerOrderShippingAddress|setShippingAddress",
            "shippingMethodName" => "shipping_method",
            "items" => "CustomerOrderItem|addItem",
            "status" => null
        ),
        "mapPush" => array(
            "orders_id" => "id",
            "customers_id" => "customerId",
            "date_purchased" => "creationDate",
            "comments" => "note",
            "orders_status" => null,
            "payment_method" => null,
            "payment_class" => null,
            "currency" => "currencyIso",
            "CustomerOrderBillingAddress|addBillingAddress|true" => "billingAddress",
            "CustomerOrderShippingAddress|addShippingAddress|true" => "shippingAddress",
            "customers_address_format_id" => null,
            "billing_address_format_id" => null,
            "delivery_address_format_id" => null,
            "shipping_class" => "shippingMethodId",
            "shipping_method" => "shippingMethodName",
            "CustomerOrderItem|addItem" => "items"
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

    public function pull($data = null, $limit = null)
    {
        return parent::pull(null, $limit);
    }

    protected function status($data)
    {
        $defaultStatus = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key="DEFAULT_ORDERS_STATUS_ID"');

        if (count($defaultStatus) > 0) {
            $defaultStatus = $defaultStatus[0]['configuration_value'];

            if ($data['orders_status'] == $defaultStatus) {
                $newStatus = $this->connectorConfig->mapping->pending;

                if (!is_null($newStatus)) {
                    $this->db->query('UPDATE orders SET orders_status='.$newStatus.' WHERE orders_id='.$data['orders_id']);

                    $orderHistory = new \stdClass();
                    $orderHistory->orders_id = $data['orders_id'];
                    $orderHistory->orders_status_id = $newStatus;
                    $orderHistory->date_added = date('Y-m-d H:i:s');

                    $this->db->insertRow($orderHistory, 'orders_status_history');    

                    $data['orders_status'] = $newStatus;            
                }
            }
        }
       
        $mapping = array_search($data['orders_status'], (array) $this->connectorConfig->mapping);

        if ($mapping == 'canceled') {
            return CustomerOrderModel::STATUS_CANCELLED;    
        } elseif ($mapping == 'completed' || $mapping == 'shipped') {
            return CustomerOrderModel::STATUS_SHIPPED;
        }
    }

    protected function paymentStatus($data)
    {
        $mapping = array_search($data['orders_status'], (array) $this->connectorConfig->mapping);
        
        if ($mapping == 'completed' || $mapping == 'paid') {
            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        }
    }

    protected function orders_status($data)
    {
        $newStatus = null;

        if ($data->getOrderStatus() == CustomerOrderModel::STATUS_CANCELLED) {
            $newStatus = 'canceled';
        } else {
            if ($data->getPaymentStatus() == CustomerOrderModel::PAYMENT_STATUS_COMPLETED && $data->getOrderStatus() == CustomerOrderModel::STATUS_SHIPPED) {
                $newStatus = 'completed';
            } else {
                if ($data->getOrderStatus() == CustomerOrderModel::STATUS_SHIPPED) {
                    $newStatus = 'shipped';
                } elseif ($data->getPaymentStatus() == CustomerOrderModel::PAYMENT_STATUS_COMPLETED) {
                    $newStatus = 'paid';
                }
            }
        }

        if (!is_null($newStatus)) {
            $mapping = (array) $this->connectorConfig->mapping;
            
            return $mapping[$newStatus];
        }
    }

    protected function paymentModuleCode($data)
    {
        return $this->paymentMapping[$data['payment_method']];
    }

    protected function payment_method($data)
    {
        $payments = array_flip($this->paymentMapping);

        return $payments[$data->getPaymentModuleCode()];
    }

    protected function payment_class($data)
    {
        $payments = array_flip($this->paymentMapping);

        return $payments[$data->getPaymentModuleCode()];
    }

    protected function customers_address_format_id($data)
    {
        return 5;
    }

    protected function billing_address_format_id($data)
    {
        return 5;
    }

    protected function delivery_address_format_id($data)
    {
        return 5;
    }

    public function push($data = null, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id)) {
            $this->clear($data->getId()->getEndpoint());
        }

        $return = parent::push($data, $dbObj);

        $orderHistory = new \stdClass();
        $orderHistory->orders_id = $id;
        $orderHistory->orders_status_id = $this->orders_status($data);
        $orderHistory->date_added = date('Y-m-d H:i:s');

        $this->db->insertRow($orderHistory, 'orders_status_history');

        return $return;
    }

    public function clear($orderId)
    {
        $queries = array(
            'DELETE FROM orders_total WHERE orders_id='.$orderId,
            'DELETE FROM orders_products_attributes WHERE orders_id='.$orderId,
            'DELETE FROM orders_products WHERE orders_id='.$orderId,
            'DELETE FROM orders WHERE orders_id='.$orderId
        );

        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }

    public function addData($model, $data)
    {
        $shipping = new \jtl\Connector\Model\CustomerOrderItem();
        $shipping->setType('shipping');
        $shipping->setName($data['shipping_method']);
        $shipping->setCustomerOrderId($this->identity($data['orders_id']));
        $shipping->settype('shipping');
        $shipping->setId($this->identity($data['shipping_class']));
        $shipping->setQuantity(1);
        $shipping->setVat(0);

        $sum = 0;

        $totalData = $this->db->query('SELECT class,value FROM orders_total WHERE orders_id='.$data['orders_id']);

        foreach ($totalData as $total) {
            if ($total['class'] == 'ot_total') {
                $sum += floatval($total['value']);
            }
            if ($total['class'] == 'ot_tax') {
                $sum -= floatval($total['value']);
            }
            if ($total['class'] == 'ot_shipping') {
                $shipping->setPrice(floatval($total['value']));
            }
        }

        $model->setTotalSum($sum);
        $model->addItem($shipping);
    }
}
