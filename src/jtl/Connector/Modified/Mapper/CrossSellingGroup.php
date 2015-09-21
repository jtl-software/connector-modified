<?php
namespace jtl\Connector\Modified\Mapper;

class CrossSellingGroup extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_xsell_grp_name",
        "query" => "SELECT * FROM products_xsell_grp_name GROUP BY products_xsell_grp_name_id",
        "identity" => "getId",
        "getMethod" => "getCrossSellingGroups",
        "mapPull" => array(
            "id" => "products_xsell_grp_name_id",
            "i18ns" => "CrossSellingGroupI18n|addI18n"
        ),
        "mapPush" => array(
            "CrossSellingGroupI18n|addI18n" => "i18ns"
        )
    );
}
