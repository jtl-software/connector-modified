<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class Image extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_images",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "image_id",
            "relationType" => "type",
            "foreignKey" => "foreignKey",
            "filename" => null
            //"sort" => "image_nr"
        )
    );

    public function pull($data, $offset, $limit)
    {
        $result = [];

        $query = 'SELECT p.*, p.products_id foreignKey, "product" type
            FROM products_images p
            LEFT JOIN jtl_connector_link l ON p.image_id = l.endpointId AND l.type = 16
            WHERE l.hostId IS NULL';
        $defaultQuery = 'SELECT CONCAT("pID_",p.products_id) image_id, p.products_image image_name, p.products_id foreignKey, 0 image_nr, "product" type
            FROM products p
            LEFT JOIN jtl_connector_link l ON CONCAT("pID_",p.products_id) = l.endpointId AND l.type = 16
            WHERE l.hostId IS NULL && p.products_image IS NOT NULL';
        $categoriesQuery = 'SELECT CONCAT("cID_",p.categories_id) image_id, p.categories_image as image_name, p.categories_id foreignKey, "category" type, 0 image_nr
            FROM categories p
            LEFT JOIN jtl_connector_link l ON CONCAT("cID_",p.categories_id) = l.endpointId AND l.type = 16
            WHERE l.hostId IS NULL && p.categories_image IS NOT NULL';

        $dbResult = $this->db->query($query);
        $dbResultDefault = $this->db->query($defaultQuery);
        $dbResultCategories = $this->db->query($categoriesQuery);

        $dbResult = array_merge($dbResult, $dbResultDefault, $dbResultCategories);

        //$current = array_slice($dbResult, $offset, $limit);
        $current = $dbResult;

        foreach ($current as $modelData) {
            $model = $this->generateModel($modelData);

            $result[] = $model;
        }

        return $result;
    }

    public function statistic()
    {
        $totalImages = 0;
        $totalImages += parent::statistic();

        $defaultProductImages = $this->db->query("SELECT count(*) as count FROM products WHERE products_image IS NOT NULL LIMIT 1", array("return" => "object"));

        if ($defaultProductImages !== null) {
            $totalImages += intval($defaultProductImages[0]->count);
        }

        $categoryImages = $this->db->query("SELECT count(*) as count FROM categories WHERE categories_image IS NOT NULL LIMIT 1", array("return" => "object"));

        if ($categoryImages !== null) {
            $totalImages += intval($categoryImages[0]->count);
        }

        return $totalImages;
    }

    protected function filename($data)
    {
        if ($data['type'] == 'category') {
            return $this->shopConfig['shop']['fullUrl'].'images/categories/'.$data['image_name'];
        } else {
            return $this->shopConfig['shop']['fullUrl'].$this->shopConfig['img']['original'].$data['image_name'];
        }
    }
}
