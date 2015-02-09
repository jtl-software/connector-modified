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
            "foreignKey" => "products_id",
            "filename" => null,
            "sort" => "image_nr",
        ),
    );

    public function pull($data, $offset, $limit)
    {
        $result = [];

        // get additional images
        $query = 'SELECT * FROM products_images';
        // get default product images
        $defaultQuery = 'SELECT products_id,products_image as image_name FROM products WHERE products_image IS NOT NULL';

        $dbResult = $this->db->query($query);
        $dbResultSub = $this->db->query($defaultQuery);

        $dbResult = array_merge($dbResult, $dbResultSub);

        $current = array_slice($dbResult, $offset, $limit);

        foreach ($current as $modelData) {
            if (!isset($modelData['image_id'])) {
                $modelData['image_id'] = 'pID_'.$modelData['products_id'];
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
        return 'product';
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
        return $this->shopConfig['shop']['fullUrl'].$this->shopConfig['img']['original'].$data['image_name'];
    }
}
