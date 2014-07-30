<?php
namespace jtl\Connector\Modified\Mapper;

class Category extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "categories",
    	"pk" => "categories_id",
    	"mapPull" => array(
        	"id" => "categories_id",
        	"parentCategoryId" => null,
        	"sort" => "sort_order|integer",
    	    "isActive" => "categories_status|boolean",
    	    "i18ns" => "CategoryI18n",
    	    "invisibilities" => "CategoryInvisibility"
        ),
        "mapPush" => array(
            "categories_id" => "id",
            "parent_id" => "parentCategoryId",
            "sort_order" => "sort"
        )
    );     

    protected function parentCategoryId($data) {
        return $this->replaceZero($data['parent_id']);
    }
}