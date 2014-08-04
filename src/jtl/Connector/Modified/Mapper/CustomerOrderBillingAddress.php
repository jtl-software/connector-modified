<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerOrderBillingAddress extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customer_orders",
        "mapPull" => array(
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
            "eMail" => "customers_email_address"
		),
        "mapPush" => array(
            "billing_firstname" => "_firstName",
            "billing_lastname" => "_lastName",
            "billing_company" => "_company",
            "billing_street_address" => "_street",
            "billing_suburb" => "_extraAddressLine",
            "billing_postcode" => "_zipCode",
            "billing_city" => "_city",
            "billing_state" => "_state",
            "billing_country_iso_code_2" => "_countryIso",
            "customers_email_address" => "_eMail"
        )
    );
    
    public function pull($data) {
       return array($this->generateModel($data));
    }
    
    public function id($data) {
    	return "cID_".$data['customers_id'];
    }
}