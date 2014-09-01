<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariationValue extends BaseMapper
{
    private $productId;
    
    protected $mapperConfig = array(
        "table" => "products_attributes",
        "query" => 'SELECT * FROM products_attributes WHERE products_id=[[products_id]] && options_id=[[options_id]]',
        "getMethod" => "getValues",
        "mapPull" => array(
        	"id" => "options_values_id",
            "productVariationId" => "options_id",
            "extraWeight" => null,
            "sku" => "attributes_model",
            "sort" => "sortorder",
            "stockLevel" => "attributes_stock",
            "i18ns" => "ProductVariationValueI18n|addI18n",
            "extraCharges" => "ProductVariationValueExtraCharge|addExtraCharge"
        ),
        "mapPush" => array(     
            "options_values_id" => null,
            "options_id" => "productVariationId",
            "attributes_model" => "sku",
            "sortorder" => "sort",
            "attributes_stock" => "stockLevel",
            "products_id" => null,
            "weight_prefix" => null,
            "options_values_weight" => null,
            "ProductVariationValueI18n|addI18n" => "i18ns",
            "ProductVariationValueExtraCharge|addExtraCharge|true" => "extraCharges"
        )
    );    
    
    protected function extraWeight($data) {
        return $data['weight_prefix'] == '-' ? $data['options_values_weight'] * -1 : $data['options_values_weight'];
    }
    
    public function push($parent,$dbObj) {
        $nextId = $this->db->query('SELECT max(products_options_values_id) + 1 AS nextID FROM products_options_values');
        $nextId = is_null($nextId[0]['nextID']) ? 1 : $nextId[0]['nextID'];
        
        $this->productId = $parent->getProductId()->getEndpoint();
        
        foreach($parent->getValues() as $value) {
            $value->setProductVariationId($parent->getId());
            $value->setAction('insert');
            
            $value->setId($this->identity($nextId));
            
            $nextId++;
        }        
        
        return parent::push($parent,$dbObj);
    }
    
    protected function options_values_id($data,$model) {
        return $data->getId()->getEndpoint();        
    }
    
    protected function products_id($data) {
        return $this->productId;
    }
    
    protected function weight_prefix($data) {
        return $data->getExtraWeight() < 0 ? '-' : '+';
    }
    
    protected function options_values_weight($data) {
        return abs($data->getExtraWeight());
    }
}