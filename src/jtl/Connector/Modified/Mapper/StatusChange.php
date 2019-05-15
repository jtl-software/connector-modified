<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\StatusChange as StatusChangeModel;
use jtl\Connector\Model\CustomerOrder;

class StatusChange extends BaseMapper
{
    public function push(StatusChangeModel $status)
    {
        $customerOrderId = (int) $status->getCustomerOrderId()->getEndpoint();

        if ($customerOrderId > 0) {
            $mapping = (array) $this->connectorConfig->mapping;
            
            $newStatus = $mapping[$this->getStatus($status)];

            if (!is_null($newStatus)) {
                $this->db->query('UPDATE orders SET orders_status='.$newStatus.' WHERE orders_id='.$customerOrderId);

                $orderHistory = new \stdClass();
                $orderHistory->orders_id = $customerOrderId;
                $orderHistory->orders_status_id = $newStatus;
                $orderHistory->date_added = date('Y-m-d H:i:s');

                $this->db->insertRow($orderHistory, 'orders_status_history');                
            }            
        }

        return $status;
    }

    private function getStatus(StatusChangeModel $status)
    {
        if ($status->getOrderStatus() == CustomerOrder::STATUS_CANCELLED) {
            return 'canceled';
        } else {
            if ($status->getPaymentStatus() == CustomerOrder::PAYMENT_STATUS_COMPLETED && $status->getOrderStatus() == CustomerOrder::STATUS_SHIPPED) {
                return 'completed';
            } else {
                if ($status->getOrderStatus() == CustomerOrder::STATUS_SHIPPED) {
                    return 'shipped';
                } elseif ($status->getPaymentStatus() == CustomerOrder::PAYMENT_STATUS_COMPLETED) {
                    return 'paid';
                }
            }
        }
    }
}
