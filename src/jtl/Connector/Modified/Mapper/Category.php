<?php
namespace jtl\Connector\Modified\Mapper;

class Category extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "categories",
        "query" => "SELECT c.* FROM categories c
            LEFT JOIN jtl_connector_link l ON c.categories_id = l.endpointId AND l.type = 0
            WHERE l.hostId IS NULL",
        "where" => "categories_id",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "categories_id",
            "parentCategoryId" => null,
            "sort" => "sort_order",
            "isActive" => "categories_status",
            "level" => "level",
            "i18ns" => "CategoryI18n|addI18n",
            "invisibilities" => "CategoryInvisibility|addInvisibility"
        ),
        "mapPush" => array(
            "categories_id" => "id",
            "parent_id" => "parentCategoryId",
            "sort_order" => "sort",
            "categories_status" => "isActive",
            "CategoryI18n|addI18n" => "i18ns",
            "CategoryInvisibility|addInvisibility|true" => "invisibilities"
        )
    );

    private $tree = array();

    protected function parentCategoryId($data)
    {
        return $this->replaceZero($data['parent_id']);
    }

    public function pull($params)
    {
        $this->tree = array();

        $this->getChildren();

        usort($this->tree, function ($a, $b) {
            return $a['level'] - $b['level'];
        });

        foreach ($this->tree as $category) {
            $result[] = $this->generateModel($category);
        }

        return $result;
    }

    private function getChildren($ids = null, $level = 0)
    {
        if (is_null($ids)) {
            $sql = 'c.parent_id=0';
        } else {
            $sql = 'c.parent_id IN ('.implode(',', $ids).')';
        }

        $children = $this->db->query('SELECT c.* FROM categories c LEFT JOIN jtl_connector_link l ON c.categories_id = l.endpointId AND l.type = 0
            WHERE l.hostId IS NULL && '.$sql);

        if (count($children) > 0) {
            $ids = array();

            foreach ($children as $child) {
                $ids[] = $child['categories_id'];

                $child['level'] = $level;
                $this->tree[] = $child;
            }

            $this->getChildren($ids, $level + 1);
        }
    }
}
