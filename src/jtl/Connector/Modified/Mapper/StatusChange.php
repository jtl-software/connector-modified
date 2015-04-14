<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Model\StatusChange as StatusChangeModel;

class StatusChange extends BaseMapper
{
    public function push(StatusChangeModel $status)
    {
        $customerOrderId = (int) $status->getCustomerOrderId()->getEndpoint();

        if ($customerOrderId > 0) {
            $combo = $status->getOrderStatus().'|'.$status->getPaymentStatus();

            $mapping = array_flip((array) $this->connectorConfig->mapping);

            if (!is_null($mapping[$combo])) {
                $newStatus = substr($mapping[$combo], strpos($mapping[$combo], '_') + 1, strlen($mapping[$combo]));
                if (!is_null($newStatus)) {
                    $this->db->query('UPDATE orders SET orders_status='.$newStatus.' WHERE orders_id='.$customerOrderId);

                    $orderHistory = new \stdClass();
                    $orderHistory->orders_id = $customerOrderId;
                    $orderHistory->orders_status_id = $newStatus;
                    $orderHistory->date_added = date('Y-m-d H:i:s');

                    $this->db->insertRow($orderHistory, 'orders_status_history');
                }
            }
        }

        return $status;
    }
}
