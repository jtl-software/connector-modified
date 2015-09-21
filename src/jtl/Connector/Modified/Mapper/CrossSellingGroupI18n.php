<?php
namespace jtl\Connector\Modified\Mapper;

class CrossSellingGroupI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_xsell_grp_name",
        "getMethod" => "getI18ns",
        "query" => "SELECT g.products_xsell_grp_name_id,g.groupname,l.code 
            FROM products_xsell_grp_name g 
            LEFT JOIN languages l ON l.languages_id=g.language_id 
            WHERE g.products_xsell_grp_name_id=[[products_xsell_grp_name_id]] && g.groupname != ''",
        "mapPull" => array(
            "crossSellingGroupId" => "products_xsell_grp_name_id",
            "name" => "groupname",
            "languageISO" => null
        )
    );

    public function push($data, $dbObj = null)
    {
        $id = $data->getId()->getEndpoint();

        if (empty($id)) {
            $nextId = $this->db->query('SELECT max(products_xsell_grp_name_id) + 1 AS nextID FROM products_xsell_grp_name');
            $id = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];
        } else {
            $this->db->query('DELETE FROM products_xsell_grp_name WHERE products_xsell_grp_name_id='.$id);
        }

        $data->getId()->setEndpoint($id);

        foreach ($data->getI18ns() as $i18n) {
            $i18n->getCrossSellingGroupId()->setEndpoint($id);

            $grp = new \stdClass();
            $grp->language_id = $this->locale2id($i18n->getLanguageISO());
            $grp->products_xsell_grp_name_id = $id;
            $grp->groupname = $i18n->getName();

            $this->db->insertRow($grp, 'products_xsell_grp_name');
        }

        return $data->getI18ns();
    }

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }
}
