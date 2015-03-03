<?php
namespace jtl\Connector\Modified\Mapper;

class CategoryI18n extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = array(
        "table" => "categories_description",
        "getMethod" => "getI18ns",
        "where" => array("categories_id","language_id"),
        "query" => "SELECT categories_description.*,languages.code
            FROM categories_description
            LEFT JOIN languages ON languages.languages_id=categories_description.language_id
            WHERE categories_description.categories_id=[[categories_id]]",
        "mapPull" => array(
            "languageISO" => null,
            "categoryId" => "categories_id",
            "name" => "categories_name",
            "description" => "categories_description"
        ),
        "mapPush" => array(
            "language_id" => null,
            "categories_id" => null,
            "categories_name" => "name",
            "categories_description" => "description"
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

    protected function categories_id($data, $return, $parent)
    {
        $return->setCategoryId($this->identity($parent->getId()->getEndpoint()));

        return $parent->getId()->getEndpoint();
    }
}
