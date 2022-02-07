<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\DataModel;

class Product2Category extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_to_categories",
        "getMethod" => "getCategories",
        "identity" => "getId",
        "where" => ["categories_id","products_id"],
        "query" => 'SELECT *,CONCAT(products_id,"_",categories_id) AS id FROM products_to_categories WHERE products_id=[[products_id]]',
        "mapPull" => [
            "id" => "id",
            "categoryId" => "categories_id",
            "productId" => "products_id"
        ],
        "mapPush" => [
            "categories_id" => "categoryId",
            "products_id" => "productId"
        ]
    ];

    public function push(DataModel $model, \stdClass $dbObj = null)
    {
        $id = $model->getId()->getEndpoint();

        if (!empty($id)) {
            $this->db->query('DELETE FROM products_to_categories WHERE products_id='.$id);
        }

        foreach ($model->getCategories() as $category) {
            $category->setProductId($model->getId());
        }

        return parent::push($model, $dbObj);
    }
}
