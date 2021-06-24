<?php
namespace jtl\Connector\Modified\Mapper;

class CrossSellingGroupI18n extends BaseMapper
{
    protected $mapperConfig = [
        "table" => "products_xsell_grp_name",
        "getMethod" => "getI18ns",
        "query" => "SELECT g.products_xsell_grp_name_id,g.groupname,l.code 
            FROM products_xsell_grp_name g 
            LEFT JOIN languages l ON l.languages_id=g.language_id 
            WHERE g.products_xsell_grp_name_id=[[products_xsell_grp_name_id]] && g.groupname != ''",
        "mapPull" => [
            "crossSellingGroupId" => "products_xsell_grp_name_id",
            "name" => "groupname",
            "languageISO" => null
        ]
    ];

    public function push($data, $dbObj = null)
    {
        /** @var \jtl\Connector\Model\CrossSellingGroupI18n $i18n */
        $i18n = $data;
        
        $grp = new \stdClass();
        $grp->language_id = $this->locale2id($i18n->getLanguageISO());
        $grp->products_xsell_grp_name_id = $i18n->getCrossSellingGroupId()->getEndpoint();
        $grp->groupname = $i18n->getName();
        $this->db->insertRow($grp, 'products_xsell_grp_name');

        return $i18n;
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
