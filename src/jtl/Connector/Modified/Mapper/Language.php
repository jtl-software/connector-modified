<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class Language extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "languages",
        "identity" => "getId",
        "mapPull" => array(
        	"id" => "languages_id",
        	"nameEnglish" => "name",
        	"nameGerman" => "name",
        	"localeName" => null,
            "isDefault" => null
        )
    );

    protected function localeName($data) {
    	return $this->fullLocale($data['code']);
    }

    protected function isDefault($data) {
        return $data['code'] == $this->shopConfig['settings']['DEFAULT_LANGUAGE'] ? true : false;
    }
}