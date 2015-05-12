<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class ProductI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_description",
        "query" => "SELECT products_description.*,languages.code
            FROM products_description
            LEFT JOIN languages ON languages.languages_id=products_description.language_id
            WHERE products_id=[[products_id]]",
        "getMethod" => "getI18ns",
        "where" => array("products_id","language_id"),
        "mapPull" => array(
            "languageISO" => null,
            "productId" => "products_id",
            "name" => "products_name",
            "description" => "products_description",
            "metaDescription" => "products_meta_description",
            "metaKeywords" => "products_meta_keywords",
            "shortDescription" => "products_short_description",
            "titleTag" => "products_meta_title",
            "unitName" => null
        ),
        "mapPush" => array(
            "language_id" => null,
            "products_id" => "productId",
            "products_name" => "name",
            "products_description" => "description",
            "products_meta_description" => "metaDescription",
            "products_meta_keywords" => "metaKeywords",
            "products_short_description" => "shortDescription",
            "products_meta_title" => "titleTag"
        )
    );

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }

    protected function unitName($data)
    {
        $sql = $this->db->query('SELECT p.products_id, v.products_vpe_name
            FROM products p
            LEFT JOIN products_vpe v ON v.products_vpe_id = p.products_vpe
            WHERE products_id='.$data['products_id'].' && v.language_id='.$data['language_id']);

        if(count($sql) > 0) {
            return $sql[0]['products_vpe_name'];
        }
    }

    public function push($parent, $dbObj = null)
    {
        $id = $parent->getId()->getEndpoint();

        if (!empty($id)) {
            $this->db->query('DELETE FROM products_description WHERE products_id='.$id);
        }

        foreach ($parent->getI18ns() as $i18n) {
            $i18n->setProductId($parent->getId());
        }

        return parent::push($parent, $dbObj);
    }
}
