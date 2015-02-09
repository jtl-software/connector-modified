<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class CategoryLevels extends Module
{
    public static $name = '<span class="glyphicon glyphicon-th-list"></span> Category levels';

    private $_tree = array();

    public function form()
    {
        $this->getChildren();

        usort($this->_tree, function ($a, $b) {
            return $a['level'] - $b['level'];
        });

        var_dump($this->_tree);
    }

    public function save()
    {
    }

    private function getChildren($ids = null, $level = 0)
    {
        if (is_null($ids)) {
            $sql = 'WHERE parent_id=0';
        } else {
            $sql = 'WHERE parent_id IN ('.implode(',', $ids).')';
        }

        $children = $this->db->query('SELECT categories_id,parent_id FROM categories '.$sql);

        if (count($children) > 0) {
            $ids = array();

            foreach ($children as $child) {
                $ids[] = $child['categories_id'];

                $child['level'] = $level;
                $this->_tree[] = $child;
            }

            $this->getChildren($ids, $level + 1);
        }
    }
}
