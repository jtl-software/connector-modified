<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;
use jtl\Connector\Core\Utilities\Language;

class Customer extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers",
        "query" => "SELECT * FROM customers c
            LEFT JOIN address_book a ON c.customers_default_address_id = a.address_book_id
            LEFT JOIN countries co ON co.countries_id = a.entry_country_id
            LEFT JOIN jtl_connector_link l ON c.customers_id = l.endpointId AND l.type = 16
            WHERE l.hostId IS NULL",
        "where" => "customers_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "customers_id",
            "customerGroupId" => "customers_status",
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
            "countryIso" => null,
            "languageISO" => null,
            "phone" => "customers_telephone",
            "fax" => "customers_fax",
            "eMail" => "customers_email_address",
            "vatNumber" => "customers_vat_id",
            "hasNewsletterSubscription" => null,
            "creationDate" => "customers_date_added"
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
            "customers_date_added" => "creationDate"
        )
    );

    protected function languageISO($data)
    {
        return $this->fullLocale(strtolower($data['countries_iso_code_2']));
    }

    protected function countryIso($data)
    {
        return $this->fullLocale(strtolower($data['countries_iso_code_2']));
    }

    protected function hasNewsletterSubscription($data)
    {
        return (is_null($data['customers_newsletter']) || $data['customers_newsletter'] == 0) ? false : true;
    }

    public function push($data, $dbObj = null)
    {
        if($data->getId()->getEndpoint() !== 0) {
            $this->db->query('DELETE FROM address_book WHERE customers_id='.$data->getId()->getEndpoint());
            $this->db->query('DELETE FROM customers_info WHERE customers_info_id='.$data->getId()->getEndpoint());
        }

        $return = parent::push($data, $dbObj);

        $iso = strtoupper(Language::map($data->getCountryIso()));
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
        $entry->address_class = 'primary';
        $entry->entry_country_id = ($countryResult) ? $countryResult[0]['countries_id'] : '81'; // if country not found set to default xtc ID for germany

        $address = $this->db->insertRow($entry, 'address_book');

        $customerUpdate = new \stdClass();
        $customerUpdate->customers_default_address_id = $address->getKey();

        $insertResult = $this->db->updateRow($customerUpdate, $this->mapperConfig['table'], 'customers_id', $data->getId()->getEndpoint());

        $addressUpdate = new \stdClass();
        $addressUpdate->customers_id = $data->getId()->getEndpoint();

        $this->db->updateRow($addressUpdate, 'address_book', 'address_book_id', $address->getKey());

        $infoObj = new \stdClass();
        $infoObj->customers_info_id = $insertResult->getKey();
        $this->db->insertRow($infoObj, 'customers_info');

        return $return;
    }
}
