<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class UnitI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT products_vpe.*,languages.code FROM products_vpe LEFT JOIN languages ON languages.languages_id=products_vpe.language_id WHERE products_vpe_id=[[products_vpe_id]]",
        "table" => "products_vpe",
        "mapPull" => array(
        	"unitId" => "products_vpe_id",
        	"localeName" => null,
        	"name" => "products_vpe_name"
        )
    );
    
    protected function localeName($data) {
    	return $this->fullLocale($data['code']);
    }
}