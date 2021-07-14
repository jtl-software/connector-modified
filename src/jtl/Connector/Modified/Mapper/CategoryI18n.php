<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\CategoryI18n as CategoryI18nModel;
use jtl\Connector\Model\Category as CategoryModel;

class CategoryI18n extends AbstractMapper
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

    protected function categories_id(CategoryI18nModel $i18n, $dbObj, CategoryModel $category)
    {
        $i18n->getCategoryId()->setEndpoint($category->getId()->getEndpoint());

        return $i18n->getCategoryId()->getEndpoint();
    }
}
