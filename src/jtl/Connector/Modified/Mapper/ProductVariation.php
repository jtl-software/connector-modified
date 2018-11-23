<?php
/**
 * Created by PhpStorm.
 * User: Niklas
 * Date: 14.11.2018
 * Time: 12:56
 */

namespace jtl\Connector\Modified\Mapper;

class ProductVariation extends BaseMapper
{
    protected $mapperConfig = array (
        "table"     => "products_options",
        "query"     => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] GROUP BY options_id',
        "where"     => "options_id",
        "getMethod" => "getVariationCombinations",
        "mapPull"   => array (
            "id"            => "options_id",
            "productId"    => "products_id",
            "sort"          => "sort_order",
            "i18ns"         => "ProductVariationI18n|addI18n",
            "values"        => "ProductVariationValue|addValue"
        )
    );
}