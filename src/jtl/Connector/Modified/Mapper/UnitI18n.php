<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class UnitI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "query" => "SELECT products_vpe.*,languages.code FROM products_vpe LEFT JOIN languages ON languages.languages_id=products_vpe.language_id WHERE products_vpe_id=[[products_vpe_id]]",
        "table" => "products_vpe",
        "getMethod" => "getI18ns",
        "mapPull" => array(
            "unitId" => "products_vpe_id",
            "languageISO" => null,
            "name" => "products_vpe_name"
        ),
        "mapPush" => array(
            "products_vpe_id" => "unitId",
            "language_id" => null,
            "products_vpe_name" => "name"  
        )
    );

    protected function languageISO($data)
    {
        return $this->fullLocale($data['code']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLanguageISO());
    }
}
