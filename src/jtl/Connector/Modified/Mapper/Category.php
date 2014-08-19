<?php
namespace jtl\Connector\Modified\Mapper;

class Category extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "categories",
        "where" => "categories_id",
        "identity" => "getId",
    	"mapPull" => array(
        	"id" => "categories_id",
        	"parentCategoryId" => null,
        	"sort" => "sort_order",
    	    "isActive" => "categories_status",
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

    protected function parentCategoryId($data) {
        return $this->replaceZero($data['parent_id']);
    }
}