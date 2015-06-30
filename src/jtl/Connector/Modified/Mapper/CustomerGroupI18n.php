<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class CustomerGroupI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "customers_status",
        "getMethod" => "getI18ns",
        "query" => "SELECT customers_status.customers_status_id,customers_status.customers_status_name,languages.code FROM customers_status LEFT JOIN languages ON languages.languages_id=customers_status.language_id WHERE customers_status.customers_status_id=[[customers_status_id]]",
        "mapPull" => array(
            "customerGroupId" => "customers_status_id",
            "name" => null,
            "languageISO" => null
        )
    );

    public function push($data, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (empty($id) && $id != 0) {
            $nextId = $this->db->query('SELECT max(customers_status_id) + 1 AS nextID FROM customers_status');
            $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
        } else {
            $this->db->query('DELETE FROM customers_status WHERE customers_status_id='.$id);
        }

        $data->getId()->setEndpoint($id);

        foreach ($data->getI18ns() as $i18n) {
            $i18n->getCustomerGroupId()->setEndpoint($id);

            $grp = new \stdClass();
            $grp->language_id = $this->locale2id($i18n->getLanguageISO());
            $grp->customers_status_id = $id;
            $grp->customers_status_name = $i18n->getName();
            $grp->customers_status_discount = $data->getDiscount();
            $grp->customers_status_ot_discount = $data->getDiscount();
            $grp->customers_status_graduated_prices = 1;
            $grp->customers_status_add_tax_ot = $data->getApplyNetPrice() === true ? 1 : 0;
            $grp->customers_status_show_price_tax = $data->getApplyNetPrice() === true ? 0 : 1;

            $this->db->insertRow($grp, 'customers_status');
        }

        return $data->getI18ns();
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function name($data)
    {
        return html_entity_decode($data['customers_status_name']);
    }
}
