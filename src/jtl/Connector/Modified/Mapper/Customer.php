<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;
use \jtl\Core\Utilities\Language;

class Customer extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers",
        "query" => "SELECT * FROM customers LEFT JOIN address_book ON customers.customers_default_address_id = address_book.address_book_id LEFT JOIN countries ON countries.countries_id = address_book.entry_country_id",
        "where" => "customers_id",
        "identity" => "getId",
        "mapPull" => array(
			"id" =>	"customers_id",
			"customerGroupId" => null,
			"customerNumber" => "customers_cid",
			"salutation" => "customers_gender",
            "birthday" => "customers_dob",
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
			"created" => "customers_date_added",
            "modified" => "customers_last_modified"					
		),
        "mapPush" => array(
            "customers_id" => "id",
            "customers_status" => "customerGroupId",
            "customers_cid" => "customerNumber",
            "customers_gender" => "salutation",
            "customers_firstname" => "firstName",
            "customers_lastname" => "lastName",
            "customers_dob" => "birthday",
            "customers_telephone" => "phone",
            "customers_fax" => "fax",
            "customers_email_address" => "eMail",
            "customers_vat_id" => "vatNumber",
            "customers_newsletter" => "hasNewsletterSubscription",
            "customers_date_added" => "created"                        
        )
    );
    
    protected function customerGroupId($data) {
        return $this->replaceZero($data['customers_status']);
    }
    
    protected function hasNewsletterSubscription($data) {
        return (is_null($data['customers_newsletter']) || $data['customers_newsletter'] == 0) ? false : true;
    }
    
    public function push($data,$dbObj=null) {
        $return = parent::push($data,$dbObj);
                
        $iso = strtoupper(Language::map($data->getLocaleName()));
        $countryResult = $this->db->query('SELECT countries_id FROM countries WHERE countries_iso_code_2="'.$iso.'"');
         
        $entry = new \stdClass();
        $entry->customers_id = $data->getId()->getEndpoint();
        $entry->entry_gender = $data->getSalutation();
        $entry->entry_company = $data->getCompany();
        $entry->entry_firstname = $data->getFirstName();
        $entry->entry_lastname = $data->getLastName();
        $entry->entry_street_address = $data->getStreet();
        $entry->entry_suburb = $data->getExtraAddressLine();
        $entry->entry_postcode = $data->getZipCode();
        $entry->entry_city = $data->getCity();
        $entry->entry_state = $data->getState();
        $entry->entry_country_id = ($countryResult) ? $countryResult[0]['countries_id'] : '81'; // if country not found set to default xtc ID for germany
        
        if($data->getAction() == "update") {
            $default = $this->db->query('SELECT customers_default_address_id FROM customers WHERE customers_id='.$data->getId()->getEndpoint());
            if($default) $entry->address_book_id = $default[0]['customers_default_address_id'];
        
            $entryUpdate = $this->db->updateRow($entry,'address_book','address_book_id',$entry->address_book_id);    
        }
        elseif($data->getAction() == "insert") {
            $address = $this->db->insertRow($entry,'address_book');
        
            $customerUpdate = new \stdClass();
            $customerUpdate->customers_default_address_id = $address->getKey();
        
            $insertResult = $this->db->updateRow($customerUpdate,$this->mapperConfig['table'],'customers_id',$data->getId()->getEndpoint());
        
            $addressUpdate = new \stdClass();
            $addressUpdate->customers_id = $data->getId()->getEndpoint();
        
            $this->db->updateRow($addressUpdate,'address_book','address_book_id',$address->getKey());
        
            $infoObj = new \stdClass();
            $infoObj->customers_info_id = $insertResult->getKey();
            $this->db->insertRow($infoObj,'customers_info');    
        } 

        return $return;
    }
}