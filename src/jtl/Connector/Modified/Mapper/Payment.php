<?php
namespace jtl\Connector\Modified\Mapper;

class Payment extends \jtl\Connector\Modified\Mapper\AbstractMapper
{
    protected $mapperConfig = [
        "table" => "jtl_connector_payment",
        "query" => "SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link_payment l ON p.id = l.endpoint_id
            LEFT JOIN jtl_connector_link_customer_order o ON o.endpoint_id = p.customerOrderId
            WHERE l.host_id IS NULL AND o.endpoint_id IS NOT NULL",
        "where" => "id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "id",
            "customerOrderId" => "customerOrderId",
            "billingInfo" => "billingInfo",
            "creationDate" => "creationDate",
            "totalSum" => "totalSum",
            "transactionId" => "transactionId",
            "paymentModuleCode" => "paymentModuleCode"
        ]
    ];
}
