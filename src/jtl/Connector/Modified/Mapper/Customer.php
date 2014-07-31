<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class Customer extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers",
        "query" => "SELECT * FROM customers LEFT JOIN address_book ON customers.customers_default_address_id = address_book.address_book_id LEFT JOIN countries ON countries.countries_id = address_book.entry_country_id",
        "mapPull" => array(
			"id" =>	"customers_id",
			"customerGroupId" => null,
			"customerNumber" => "customers_cid",
			"salutation" => "customers_gender",
			"firstName" => "customers_firstname",
			"lastName" => "customers_lastname",
			"company" => "entry_company",
			"street" => "entry_street_address",
			"extraAddressLine" => "entry_suburb",
			"zipCode" => "entry_postcode",
			"city" => "entry_city",
			"countryIso" => "countries_iso_code_2",
			"phone" => "customers_telephone",
			"fax" => "customers_fax",
			"eMail" => "customers_email_address",
			"vatNumber" => "customers_vat_id",
			"hasNewsletterSubscription" => null,
			"created" => null						
		),
        "mapPush" => array(
            "customers_id" => "_id",
            "customers_status" => "_customerGroupId",
            "customers_cid" => "_customerNumber",
            "customers_password" => "_password",
            "customers_gender" => "_salutation",
            "customers_firstname" => "_firstName",
            "customers_lastname" => "_lastName",
            "customers_telephone" => "_phone",
            "customers_fax" => "_fax",
            "customers_email_address" => "_eMail",
            "customers_vat_id" => "_vatNumber",
            "customers_newsletter" => "_hasNewsletterSubscription",
            "customers_dob" => null,
            "customers_date_added" => null,
            "customers_last_modified" => null
        )
    );
    
    protected function customerGroupId($data) {
        return $this->replaceZero($data['customers_status']);
    }
    
    protected function created($data) {
        return new \DateTime($data['customers_date_added']);       
    }
    
    protected function hasNewsletterSubscription($data) {
        return (is_null($data['customers_newsletter']) || $data['customers_newsletter'] == 0) ? false : true;
    }
}