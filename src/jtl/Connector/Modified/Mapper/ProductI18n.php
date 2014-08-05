<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_description",
        "query" => "SELECT products_description.*,languages.code 
            FROM products_description 
            LEFT JOIN languages ON languages.languages_id=products_description.language_id 
            WHERE products_id=[[products_id]]",
        "mapPull" => array(
        	"localeName" => null,
            "productId" => "products_id",
            "name" => "products_name",
            "urlPath" => "products_url",
            "description" => "products_description",
            "metaDescription" => "products_meta_description",
            "metaKeywords" => "products_meta_keywords",
            "shortDescription" => null                     
        ),
        "mapPush" => array(
            "language_id" => null,
            "products_id" => "_productId",
            "products_name" => "_name",
            "products_url" => "_urlPath",
            "products_description" => "_description",
            "products_short_description" => "_shortDescription"
        )
    );
        
    protected function localeName($data) {
    	return $this->fullLocale($data['code']);
    }

    protected function shortDescription($data) {
        return !is_null($data['products_short_description']) ? $data['products_short_description'] : '';
    }  
}