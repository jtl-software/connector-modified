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
        "mapPull" => array(
        	"id" => "options_id",
        	"productId" => "products_id",
        	"type" => null,
            "i18ns" => "ProductVariationI18n|addI18n",
            "values" => "ProductVariationValue|addValue"
        ),
        "mapPush" => array( 
            "options_id" => "id",
            "ProductVariationI18n|addI18n" => "i18ns"
            //"ProductVariationValue|addValue" => "values"
        )
    );    
    
    protected function type($data) {
        return "select";
    }
    
    public function push($data,$dbObj) {
        foreach($data->getVariations() as $variation) {
            $nextId = $this->db->query("SELECT MIN( t1.products_options_id +1 ) AS nextID
                FROM products_options t1
                LEFT JOIN products_options t2 ON t1.products_options_id +1 = t2.products_options_id
                WHERE t2.products_options_id IS NULL");
            $nextId = $nextId[0];
            
            $variationIdentity = $this->identity($nextId['nextID']); 
            
            $variation->setAction(null);
            $variation->setId($variationIdentity);            
        }
        
        return parent::push($data,$dbObj);
    }
    
}