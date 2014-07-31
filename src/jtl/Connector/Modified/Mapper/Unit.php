<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class Unit extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT products_vpe.*,languages.code FROM products_vpe LEFT JOIN languages ON languages.languages_id=products_vpe.language_id",
        "table" => "products_vpe",
        "mapPull" => array(
        	//"id" => "products_vpe_id",
        	"localeName" => "code",
        	"name" => "products_vpe_name"
        )
    );
    
    protected function localeName($data) {
    	return $this->fullLocale($data['code']);
    }
}