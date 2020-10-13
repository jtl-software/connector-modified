<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Model\CustomerOrderItem;

class CustomerOrder extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "orders",
        "statisticsQuery" => "SELECT COUNT(o.orders_id) as total FROM orders o
            LEFT JOIN jtl_connector_link_customer_order l ON o.orders_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "query" => "SELECT o.* FROM orders o
            LEFT JOIN jtl_connector_link_customer_order l ON o.orders_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "orders_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "orders_id",
            "orderNumber" => "orders_id",
            "customerId" => "customers_id",
            "creationDate" => "date_purchased",
            "note" => "comments",
            "paymentModuleCode" => null,
            "languageISO" => null,
            "currencyIso" => "currency",
            "billingAddress" => "CustomerOrderBillingAddress|setBillingAddress",
            "shippingAddress" => "CustomerOrderShippingAddress|setShippingAddress",
            "shippingMethodName" => "shipping_method",
            "items" => "CustomerOrderItem|addItem",
            "status" => null,
            "paymentStatus" => null
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

    public function __construct()
    {
        parent::__construct();

        if (!empty($this->connectorConfig->from_date)) {
            $this->mapperConfig['query'] .= ' && date_purchased >= "' . $this->connectorConfig->from_date . '"';
        }
    }

    protected function languageISO($data)
    {
        return $this->id2locale($data['languages_id']);
    }

    protected function status($data)
    {
        $defaultStatus = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key="DEFAULT_ORDERS_STATUS_ID"');

        if (count($defaultStatus) > 0) {
            $defaultStatus = $defaultStatus[0]['configuration_value'];

            if ($data['orders_status'] == $defaultStatus) {
                $newStatus = $this->connectorConfig->mapping->pending;

                if (!is_null($newStatus)) {
                    $this->db->query('UPDATE orders SET orders_status=' . $newStatus . ' WHERE orders_id=' . $data['orders_id']);

                    $orderHistory = new \stdClass();
                    $orderHistory->orders_id = $data['orders_id'];
                    $orderHistory->orders_status_id = $newStatus;
                    $orderHistory->date_added = date('Y-m-d H:i:s');
                    $this->db->insertRow($orderHistory, 'orders_status_history');
                    $data['orders_status'] = $newStatus;
                }
            }
        }

        $mapping = array_search($data['orders_status'], (array)$this->connectorConfig->mapping);

        if ($mapping == 'canceled') {
            return CustomerOrderModel::STATUS_CANCELLED;
        } elseif ($mapping == 'completed' || $mapping == 'shipped') {
            return CustomerOrderModel::STATUS_SHIPPED;
        }
    }

    protected function paymentStatus($data)
    {
        $mapping = array_search($data['orders_status'], (array)$this->connectorConfig->mapping);

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
            $mapping = (array)$this->connectorConfig->mapping;

            return $mapping[$newStatus];
        }
    }

    protected function paymentModuleCode($data)
    {
        if (key_exists($data['payment_method'], $this->paymentMapping)) {
            return $this->paymentMapping[$data['payment_method']];
        }

        return $data['payment_method'];
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
            'DELETE FROM orders_total WHERE orders_id=' . $orderId,
            'DELETE FROM orders_products_attributes WHERE orders_id=' . $orderId,
            'DELETE FROM orders_products WHERE orders_id=' . $orderId,
            'DELETE FROM orders WHERE orders_id=' . $orderId
        );

        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }

    /**
     * @param $ordersId
     * @return bool|float
     */
    public function determineDefaultTaxRate($ordersId)
    {
        $sql = sprintf('SELECT MAX(`products_tax`) `tax_rate` FROM `orders_products` WHERE `orders_id` = %d', $ordersId);
        $taxRate = $this->db->query($sql);
        return isset($taxRate[0]['tax_rate']) ? (float)$taxRate[0]['tax_rate'] : 0.;
    }


    /**
     * @param \jtl\Connector\Model\CustomerOrder $model
     * @param $data
     */
    public function addData($model, $data)
    {
        $defaultTaxRate = $this->determineDefaultTaxRate($data['orders_id']);

        $totalData = $this->db->query('SELECT class,value,title FROM orders_total WHERE orders_id=' . $data['orders_id'].' ORDER BY sort_order ASC');
        foreach ($totalData as $total) {
            switch ($total['class']) {
                case 'ot_total':
                    $model->setTotalSum(floatval($total['value']));
                    break;
                case 'ot_shipping':
                    $model->addItem($this->createShippingItem($total, $data));
                    $model->setShippingMethodName($total['title']);
                    break;
                case 'ot_coupon':
                case 'ot_discount':
                case 'ot_gv':
                    $model->addItem($this->createOrderItem(CustomerOrderItem::TYPE_COUPON, $total, $data, 1, $defaultTaxRate));
                    break;
                case 'ot_payment':
                    $model->addItem($this->createOrderItem(CustomerOrderItem::TYPE_PRODUCT, $total, $data, 1, $defaultTaxRate));
                    break;
                case 'ot_cod_fee':
                    $model->addItem($this->createOrderItem(CustomerOrderItem::TYPE_SURCHARGE, $total, $data, 1, $defaultTaxRate));
                    break;
            }
        }
    }

    /**
     * @param array $total
     * @param array $data
     * @return CustomerOrderItem
     */
    protected function createShippingItem(array $total, array $data)
    {
        $shipping = new CustomerOrderItem();
        $shipping->setType(CustomerOrderItem::TYPE_SHIPPING);
        $shipping->setCustomerOrderId($this->identity($data['orders_id']));
        $shipping->setId($this->identity($data['shipping_class']));
        $shipping->setQuantity(1);

        $vat = 0;
        $price = floatval($total['value']);

        list($shippingModule, $shippingName) = explode('_', $data['shipping_class']);

        $moduleTaxClass = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key ="MODULE_SHIPPING_' . strtoupper($shippingModule) . '_TAX_CLASS"');
        if (count($moduleTaxClass) > 0) {
            if (!empty($moduleTaxClass[0]['configuration_value']) && !empty($data['delivery_country_iso_code_2'])) {
                $rateResult = $this->db->query('SELECT r.tax_rate FROM countries c
                          LEFT JOIN zones_to_geo_zones z ON z.zone_country_id = c.countries_id
                          LEFT JOIN tax_rates r ON r.tax_zone_id = z.geo_zone_id
                          WHERE c.countries_iso_code_2 = "' . $data['delivery_country_iso_code_2'] . '" && r.tax_class_id=' . $moduleTaxClass[0]['configuration_value']);

                if (count($rateResult) > 0 && isset($rateResult[0]['tax_rate'])) {
                    $vat = floatval($rateResult[0]['tax_rate']);
                }
            }
        }

        $shipping->setPriceGross($price);
        $shipping->setVat($vat);
        $shipping->setName($total['title']);

        return $shipping;
    }

    /**
     * @param $type
     * @param array $total
     * @param array $data
     * @param int $quantity
     * @param int $vat
     * @return CustomerOrderItem
     */
    protected function createOrderItem($type, array $total, array $data, $quantity = 1, $vat = 0)
    {
        $priceGross = floatval($total['value']);
        if ($type === CustomerOrderItem::TYPE_COUPON && $priceGross > 0) {
            $priceGross *= -1;
        }

        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->setType($type);
        $customerOrderItem->setName($total['title']);
        $customerOrderItem->setCustomerOrderId($this->identity($data['orders_id']));
        $customerOrderItem->setId($this->identity($total['orders_total_id']));
        $customerOrderItem->setQuantity($quantity);
        $customerOrderItem->setVat($vat);
        $customerOrderItem->setPriceGross($priceGross);

        return $customerOrderItem;
    }
}
