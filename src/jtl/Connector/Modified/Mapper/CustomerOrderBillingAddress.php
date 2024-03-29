<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Utilities\Country;

class CustomerOrderBillingAddress extends AbstractMapper
{
    protected $mapperConfig = [
        "getMethod" => "getBillingAddress",
        "mapPull" => [
            "id" => null,
            "customerId" => "customers_id",
            "firstName" => "billing_firstname",
            "lastName" => "billing_lastname",
            "company" => "billing_company",
            "street" => "billing_street_address",
            "extraAddressLine" => "billing_suburb",
            "zipCode" => "billing_postcode",
            "city" => "billing_city",
            "state" => "billing_state",
            "countryIso" => "billing_country_iso_code_2",
            "eMail" => "customers_email_address",
            "phone" => "customers_telephone",
            "vatNumber" => "customers_vat_id"
        ],
        "mapPush" => [
            "customers_name" => null,
            "customers_lastname" => "lastName",
            "customers_firstname" => "firstName",
            "customers_company" => "company",
            "customers_street_address" => "street",
            "customers_suburb" => "extraAddressLine",
            "customers_postcode" => "zipCode",
            "customers_city" => "city",
            "customers_state" => "state",
            "billing_name" => null,
            "billing_firstname" => "firstName",
            "billing_lastname" => "lastName",
            "billing_company" => "company",
            "billing_street_address" => "street",
            "billing_suburb" => "extraAddressLine",
            "billing_postcode" => "zipCode",
            "billing_city" => "city",
            "billing_state" => "state",
            "billing_country_iso_code_2" => "countryIso",
            "customers_email_address" => "eMail",
            "customers_vat_id" => "vatNumber"
        ]
    ];

    public function pull($data = null, $limit = null): array
    {
        return [$this->generateModel($data)];
    }

    protected function id($data)
    {
        return "cID_".$data['customers_id'];
    }

    public function push($parent, $dbObj = null)
    {
        $this->generateDbObj($parent->getBillingAddress(), $dbObj, null, true);
    }

    protected function customers_name($data)
    {
        return $data->getFirstName().' '.$data->getLastName();
    }

    protected function billing_name($data)
    {
        return $data->getFirstName().' '.$data->getLastName();
    }
    /*
    protected function street($data)
    {
        return utf8_encode($data['billing_street_address']);
    }
    */
}
