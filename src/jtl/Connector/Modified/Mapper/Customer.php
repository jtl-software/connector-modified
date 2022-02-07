<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Core\Utilities\Country;
use jtl\Connector\Model\DataModel;

class Customer extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "customers",
        "statisticsQuery" => "SELECT COUNT(c.customers_id) as total FROM customers c
            LEFT JOIN address_book a ON c.customers_default_address_id = a.address_book_id
            LEFT JOIN countries co ON co.countries_id = a.entry_country_id
            LEFT JOIN jtl_connector_link_customer l ON c.customers_id = l.endpoint_id
            WHERE l.host_id IS NULL && c.customers_status != 0            
            ORDER BY c.customers_date_added",
        "query" => "SELECT *, c.customers_id FROM customers c
            LEFT JOIN address_book a ON c.customers_default_address_id = a.address_book_id
            LEFT JOIN countries co ON co.countries_id = a.entry_country_id
            LEFT JOIN jtl_connector_link_customer l ON c.customers_id = l.endpoint_id
            WHERE l.host_id IS NULL && c.customers_status != 0            
            ORDER BY c.customers_date_added",
        "where" => "customers_id",
        "identity" => "getId",
        "mapPull" => [
            "id" => "customers_id",
            "customerGroupId" => "customers_status",
            "customerNumber" => "customers_cid",
            "salutation" => null,
            "birthday" => "customers_dob",
            "firstName" => "customers_firstname",
            "lastName" => "customers_lastname",
            "company" => "entry_company",
            "street" => "entry_street_address",
            "extraAddressLine" => "entry_suburb",
            "zipCode" => "entry_postcode",
            "city" => "entry_city",
            "countryIso" => "countries_iso_code_2",
            "languageISO" => null,
            "phone" => "customers_telephone",
            "fax" => "customers_fax",
            "eMail" => "customers_email_address",
            "vatNumber" => "customers_vat_id",
            "hasNewsletterSubscription" => null,
            "creationDate" => "customers_date_added",
            "hasCustomerAccount" => null
        ],
        "mapPush" => [
            "customers_id" => "id",
            "customers_status" => "customerGroupId",
            "customers_cid" => "customerNumber",
            "customers_gender" => null,
            "customers_firstname" => "firstName",
            "customers_lastname" => "lastName",
            "customers_dob" => "birthday",
            "customers_telephone" => "phone",
            "customers_fax" => "fax",
            "customers_email_address" => "eMail",
            "customers_vat_id" => "vatNumber",
            "customers_newsletter" => "hasNewsletterSubscription",
            "customers_date_added" => "creationDate",
            "customers_password" => null
        ]
    ];

    /**
     * @param $data
     * @return bool
     */
    protected function hasCustomerAccount($data)
    {
        return !$data["account_type"];
    }

    protected function salutation($data)
    {
        if ($data['customers_gender'] == 'm') {
            return 'm';
        } elseif ($data['customers_gender'] == 'f') {
            return 'w';
        }
    }

    protected function customers_gender($data)
    {
        if ($data->getSalutation() == 'm') {
            return 'm';
        } else {
            return 'f';
        }
    }

    protected function languageISO($data)
    {
        if (!empty($data['countries_iso_code_2'])) {
            return $this->fullLocale(strtolower($data['countries_iso_code_2']));
        }
    }

    protected function hasNewsletterSubscription($data)
    {
        return (is_null($data['customers_newsletter']) || $data['customers_newsletter'] == 0) ? false : true;
    }

    protected function customers_password($data)
    {
        $id = $data->getId()->getEndpoint();

        if (!empty($id)) {
            $password = $this->db->query('SELECT customers_password FROM customers WHERE customers_id = '.$id);
            $password = $password[0]['customers_password'];
        }

        return isset($password) ? $password : md5(rand());
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $id = $model->getId()->getEndpoint();
        
        if (!is_null($id)) {
            $this->db->query('DELETE FROM address_book WHERE customers_id='.$id);
            $this->db->query('DELETE FROM customers_info WHERE customers_info_id='.$id);
        }

        $return = parent::push($model, $dbObj);

        $dataIso = $model->getCountryIso();

        if (!empty($dataIso)) {
            $countryResult = $this->db->query('SELECT countries_id FROM countries WHERE countries_iso_code_2="'.$dataIso.'"');
        }

        $entry = new \stdClass();
        $entry->customers_id = $model->getId()->getEndpoint();
        $entry->entry_gender = $model->getSalutation() == 'm' ? 'm' : 'f';
        $entry->entry_company = $model->getCompany();
        $entry->entry_firstname = $model->getFirstName();
        $entry->entry_lastname = $model->getLastName();
        $entry->entry_street_address = $model->getStreet();
        $entry->entry_suburb = $model->getExtraAddressLine();
        $entry->entry_postcode = $model->getZipCode();
        $entry->entry_city = $model->getCity();
        $entry->entry_state = $model->getState();
        $entry->entry_country_id = isset($countryResult[0]['countries_id']) ? $countryResult[0]['countries_id'] : '81';

        $address = $this->db->insertRow($entry, 'address_book');

        $customerUpdate = new \stdClass();
        $customerUpdate->customers_default_address_id = $address->getKey();

        $insertResult = $this->db->updateRow($customerUpdate, $this->mapperConfig['table'], 'customers_id', $model->getId()->getEndpoint());

        $addressUpdate = new \stdClass();
        $addressUpdate->customers_id = $model->getId()->getEndpoint();

        $this->db->updateRow($addressUpdate, 'address_book', 'address_book_id', $address->getKey());

        $infoObj = new \stdClass();
        $infoObj->customers_info_id = $insertResult->getKey();
        $this->db->insertRow($infoObj, 'customers_info');
      
        return $return;
    }

    public function delete(DataModel $data)
    {
        try {
            $this->db->query('DELETE FROM customers WHERE customers_id='.$data->getId()->getEndpoint());
            $this->db->query('DELETE FROM address_book WHERE customers_id='.$data->getId()->getEndpoint());
            $this->db->query('DELETE FROM customers_info WHERE customers_info_id='.$data->getId()->getEndpoint());
            
            $this->db->query('DELETE FROM jtl_connector_link_customer WHERE endpoint_id='.$data->getId()->getEndpoint());
        } catch (\Exception $e) {
        }

        return $data;
    }
}
