<?php
namespace jtl\Connector\Modified\Mapper;

class CategoryI18n extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "categories_description",
        "query" => "SELECT categories_description.*,languages.code 
            FROM categories_description 
            LEFT JOIN languages ON languages.languages_id=categories_description.language_id 
            WHERE categories_description.categories_id=[[categories_id]]",
        "mapPull" => array(
    		"localeName" => null,
    		"categoryId" => "categories_id",
    		"name" => "categories_name",
    		"description" => "categories_description"
    	),
        "mapPush" => array(
            "language_id" => null,
            "categories_id" => "_categoryId",
            "categories_name" => "_name",
            "categories_description" => "_description"
        )
    );
    
    protected function localeName($data) {
        return $this->fullLocale($data['code']);
    }
}