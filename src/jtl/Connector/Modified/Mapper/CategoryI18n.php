<?php
namespace jtl\Connector\Modified\Mapper;

class CategoryI18n extends \jtl\Connector\Modified\Mapper\BaseMapper
{
    protected $mapperConfig = [
        "table" => "categories_description",
        "getMethod" => "getI18ns",
        "where" => ["categories_id","language_id"],
        "query" => "SELECT categories_description.*,languages.code
            FROM categories_description
            LEFT JOIN languages ON languages.languages_id=categories_description.language_id
            WHERE categories_description.categories_id=[[categories_id]]",
        "mapPull" => [
            "languageISO" => null,
            "categoryId" => "categories_id",
            "name" => "categories_name",
            "description" => "categories_description",
            "metaDescription" => "categories_meta_description",
            "metaKeywords" => "categories_meta_keywords",
            "titleTag" => "categories_meta_title"
        ],
        "mapPush" => [
            "language_id" => null,
            "categories_id" => null,
            "categories_name" => "name",
            "categories_description" => "description",
            "categories_meta_description" => "metaDescription",
            "categories_meta_keywords" => "metaKeywords",
            "categories_meta_title" => "titleTag",
            "categories_heading_title" => "name"
        ]
    ];

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
