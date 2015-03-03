<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrderShippingAddress extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customer_orders",
        "getMethod" => "getShippingAddress",
        "mapPull" => array(
            "id" => null,
            "customerId" => "customers_id",
            "firstName" => "delivery_firstname",
            "lastName" => "delivery_lastname",
            "company" => "delivery_company",
            "street" => "delivery_street_address",
            "extraAddressLine" => "delivery_suburb",
            "zipCode" => "delivery_postcode",
            "city" => "delivery_city",
            "state" => "delivery_state",
            "countryIso" => "delivery_country_iso_code_2",
            "eMail" => "customers_email_address",
            "phone" => "customers_telephone"
        ),
        "mapPush" => array(
            "delivery_name" => null,
            "delivery_firstname" => "firstName",
            "delivery_lastname" => "lastName",
            "delivery_company" => "company",
            "delivery_street_address" => "street",
            "delivery_suburb" => "extraAddressLine",
            "delivery_postcode" => "zipCode",
            "delivery_city" => "city",
            "delivery_state" => "state",
            "delivery_country_iso_code_2" => "countryIso"
        )
    );

    public function pull($data)
    {
        return array($this->generateModel($data));
    }

    protected function id($data)
    {
        return "cID_".$data['customers_id'];
    }

    public function push($parent, $dbObj)
    {
        $this->generateDbObj($parent->getShippingAddress(), $dbObj, null, true);
    }

    protected function delivery_name($data)
    {
        return $data->getFirstName().' '.$data->getLastName();
    }
}
