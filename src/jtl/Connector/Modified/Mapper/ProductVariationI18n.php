<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariationI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_options WHERE products_options_id=[[options_id]]',
        "mapPull" => array(
            "productVariationId" => "products_options_id",
            "name" => "products_options_name",
            "localeName" => null,
        ),
        "mapPush" => array(
            "products_options_id" => "productVariationId",
            "products_options_name" => "name",
            "language_id" => null,
        ),
    );

    protected function localeName($data)
    {
        return $this->id2locale($data['language_id']);
    }

    protected function language_id($data)
    {
        return $this->locale2id($data->getLocaleName());
    }

    public function push($parent, $dbObj)
    {
        foreach ($parent->getI18ns() as $i18n) {
            $i18n->setAction('insert');
            $i18n->setProductVariationId($parent->getId());
        }

        return parent::push($parent->getI18ns(), $dbObj);
    }
}
