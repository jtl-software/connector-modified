<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariation extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] GROUP BY options_id',
        "where" => "options_id",
        "getMethod" => "getVariations",
        "identity" => "getId",
        "mapPull" => array(
            "id" => "options_id",
            "productId" => "products_id",
            "sort" => "sort_order",
            "i18ns" => "ProductVariationI18n|addI18n",
            //"values" => "ProductVariationValue|addValue"
        ),
        "mapPush" => array(
            //"products_options_id" => "id",
            "ProductVariationI18n|addI18n" => "i18ns",
            //"ProductVariationValue|addValue" => "values"
        )
    );

    public function push($parent, $dbObj)
    {
        $nextId = $this->db->query('SELECT max(products_options_id) + 1 AS nextID FROM products_options');
        $nextId = is_null($nextId[0]['nextID']) || $nextId[0]['nextID'] === 0 ? 1 : $nextId[0]['nextID'];

        foreach ($parent->getVariations() as $variation) {
            if (!empty($variation->getId()->getEndpoint())) {
                $this->db->query('DELETE FROM products_attributes WHERE products_id='.$variation->getProductId()->getEndpoint());

                $checkRelations = $this->db->query("SELECT products_attributes_id FROM products_attributes WHERE options_id=".$variation->getId()->getEndpoint()." GROUP BY products_id");

                if (count($checkRelations) == 0) {
                    $this->db->query('DELETE FROM products_options WHERE products_options_id='.$variation->getId()->getEndpoint());
                    $this->db->query('DELETE products_options_values_to_products_options,products_options_values FROM products_options_values_to_products_options LEFT JOIN products_options_values ON products_options_values.products_options_values_id=products_options_values_to_products_options.products_options_values_id WHERE products_options_values_to_products_options.products_options_id='.$variation->getId()->getEndpoint());
                }
            }

            $variation->setId($this->identity($nextId));
            $variation->setProductId($parent->getId());

            $nextId++;
        }

//!!!!!!!!!!!!!!! neue ID wird nicht in submapper übergeben
var_dump($parent->getVariations());
die();

        return parent::push($parent, $dbObj);
    }
}
