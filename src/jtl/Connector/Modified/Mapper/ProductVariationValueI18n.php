<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariationValueI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options_values",
        "query" => 'SELECT * FROM products_options_values WHERE products_options_values_id=[[options_values_id]]',
        "getMethod" => "getI18ns",
        "mapPull" => array(
            "productVariationValueId" => "products_options_values_id",
            "name" => "products_options_values_name",
            "localeName" => null,
        ),
        "mapPush" => array(
            "products_options_values_id" => null,
            "products_options_values_name" => "name",
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
            $i18n->setProductVariationValueId($parent->getId());

            $value2option = new \stdClass();
            $value2option->products_options_values_id = $i18n->getProductVariationValueId()->getEndpoint();
            $value2option->products_options_id = $parent->getProductVariationId()->getEndpoint();

            $this->db->deleteInsertRow($value2option, 'products_options_values_to_products_options', 'products_options_values_id', $value2option->products_options_values_id);
        }

        return parent::push($parent, $dbObj);
    }

    protected function products_options_values_id($data, $obj, $parent)
    {
        return $parent->getId()->getEndpoint();
    }
}
