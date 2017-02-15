<?php
namespace jtl\Connector\Modified\Mapper;

class Payment extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "jtl_connector_payment",
        "query" => "SELECT p.* FROM jtl_connector_payment p
            LEFT JOIN jtl_connector_link_payment l ON p.id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where" => "id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "id",
            "customerOrderId" => "customerOrderId",
            "billingInfo" => "billingInfo",
            "creationDate" => "creationDate",
            "totalSum" => "totalSum",
            "transactionId" => "transactionId",
            "paymentModuleCode" => "paymentModuleCode"
        )
    );
}
