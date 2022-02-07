<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\DataModel;

class ProductI18n extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_description",
        "query" => "SELECT products_description.*,languages.code
            FROM products_description
            LEFT JOIN languages ON languages.languages_id=products_description.language_id
            WHERE products_id=[[products_id]]",
        "getMethod" => "getI18ns",
        "where" => ["products_id","language_id"],
        "mapPull" => [
            "languageISO" => null,
            "productId" => "products_id",
            "name" => "products_name",
            "description" => "products_description",
            "metaDescription" => "products_meta_description",
            "metaKeywords" => "products_meta_keywords",
            "shortDescription" => "products_short_description",
            "titleTag" => "products_meta_title",
            "unitName" => null,
            "deliveryStatus" => null
        ],
        "mapPush" => [
            "language_id" => null,
            "products_id" => "productId",
            "products_name" => "name",
            "products_description" => "description",
            "products_meta_description" => "metaDescription",
            "products_meta_keywords" => "metaKeywords",
            "products_short_description" => "shortDescription",
            "products_meta_title" => "titleTag"
        ]
    ];
    
    protected function deliveryStatus($data)
    {
        $query = $this->db->query('SELECT s.shipping_status_name
            FROM shipping_status s
            LEFT JOIN products p ON p.products_shippingtime = s.shipping_status_id
            WHERE p.products_id ='.$data['products_id'].' && s.language_id ='.$data['language_id']);

        if (count($query) > 0) {
            return $query[0]['shipping_status_name'];
        }
    }

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

        if (count($sql) > 0) {
            return $sql[0]['products_vpe_name'];
        }
    }

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $id = $model->getId()->getEndpoint();

        if (!empty($id)) {
            $this->db->query('DELETE FROM products_description WHERE products_id='.$id);
        }

        foreach ($model->getI18ns() as $i18n) {
            $i18n->setProductId($model->getId());
        }

        return parent::push($model, $dbObj);
    }
}
