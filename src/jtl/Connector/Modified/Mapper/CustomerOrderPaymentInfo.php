<?php

namespace jtl\Connector\Modified\Mapper;

/**
 * Class CustomerOrderPaymentInfo
 * @package jtl\Connector\Modified\Mapper
 */
class CustomerOrderPaymentInfo extends AbstractMapper
{
    /**
     * @var array
     */
    protected $mapperConfig = [
        "table" => "banktransfer",
        "query" => "SELECT * FROM banktransfer WHERE orders_id=[[orders_id]]",
        "mapPull" => [
            'id' => 'orders_id',
            'customerOrderId' => 'orders_id',
            'iban' => 'banktransfer_iban',
            'bic' => 'banktransfer_bic',
            'accountHolder' => 'banktransfer_owner',
            'bankName' => 'banktransfer_bankname',
            'bankCode' => 'banktransfer_blz',
            'accountNumber' => 'banktransfer_number',
        ]
    ];
}
