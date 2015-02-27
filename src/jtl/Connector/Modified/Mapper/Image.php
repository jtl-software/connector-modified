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
            "relationType" => null,
            "foreignKey" => null,
            "filename" => null,
            //"sort" => "image_nr",
        ),
    );

    public function pull($data, $offset, $limit)
    {
        $result = [];

        // get additional images
        $query = 'SELECT * FROM products_images';
        // get default product images
        $defaultQuery = 'SELECT products_id,products_image as image_name FROM products WHERE products_image IS NOT NULL';
        // get category images
        $categoriesQuery = 'SELECT categories_id,categories_image as image_name FROM categories WHERE categories_image IS NOT NULL';

        $dbResult = $this->db->query($query);
        $dbResultSub = $this->db->query($defaultQuery);
        $dbResultCategories = $this->db->query($categoriesQuery);

        $dbResult = array_merge($dbResult, $dbResultSub, $dbResultCategories);

        //$current = array_slice($dbResult, $offset, $limit);
        $current = $dbResult;

        foreach ($current as $modelData) {
            if (!isset($modelData['image_id'])) {
                if (isset($modelData['products_id'])) {
                    $modelData['image_id'] = 'pID_'.$modelData['products_id'];
                } else {
                    $modelData['image_id'] = 'cID_'.$modelData['categories_id'];
                }
            }
            if (!isset($modelData['image_nr'])) {
                $modelData['image_nr'] = 0;
            }

            $model = $this->generateModel($modelData);

            $result[] = $model;
        }

        return $result;
    }

    protected function relationType($data)
    {
        if (isset($data['categories_id'])) {
            return 'category';
        } else {
            return 'product';
        }
    }

    protected function foreignKey($data)
    {
        return isset($data['categories_id']) ? $data['categories_id'] : $data['products_id'];
    }

    public function statistic()
    {
        $totalImages = 0;
        $totalImages += parent::statistic();

        $objs = $this->db->query("SELECT count(*) as count FROM products WHERE products_image != '' LIMIT 1", array("return" => "object"));
        if ($objs !== null) {
            $totalImages += intval($objs[0]->count);
        }

        return $totalImages;
    }

    protected function filename($data)
    {
        if (isset($data['categories_id'])) {
            return $this->shopConfig['shop']['fullUrl'].'images/categories/'.$data['image_name'];
        } else {
            return $this->shopConfig['shop']['fullUrl'].$this->shopConfig['img']['original'].$data['image_name'];
        }
    }
}
