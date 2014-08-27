<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Connector\Modified\Mapper\BaseMapper;

class ProductVariationI18n extends BaseMapper
{
    protected $mapperConfig = array(
        "table" => "products_options",
        "query" => 'SELECT * FROM products_options WHERE products_options_id=[[options_id]]',
        "mapPull" => array(
        	"productVariationId" => "products_options_id",
        	"name" => "products_options_name",
        	"localeName" => null
        ),
        "mapPush" => array(    
            "products_options_id" => "productVariationId",
            "products_options_name" => "name",
            "language_id" => null 
        )
    );    
    
    protected function localeName($data) {
        return $this->id2locale($data['language_id']);
    }
    
    protected function language_id($data) {
        return $this->locale2id($data->getLocaleName());
    }
    
    public function push($parent,$dbObj) {
        if($parent->getAction() == 'update') {
            $checkRelations = $this->db->query("SELECT COUNT(*) FROM products_attributes WHERE options_id=".$parent->getId()->getEndpoint()." GROUP BY products_id");
            
            if(count($checkRelations) == 1) {
                // option und values löschen
                //echo "option und values löschen";
            }
            
            //echo "attributes löschen";
            //$this->db->query('DELETE FROM products_attributes WHERE options_id='.$parent->getId()->getEndpoint());
        }
        
        $nextId = $this->db->query("SELECT MIN( t1.products_options_id +1 ) AS nextID
            FROM products_options t1
            LEFT JOIN products_options t2 ON t1.products_options_id +1 = t2.products_options_id
            WHERE t2.products_options_id IS NULL");        
        $nextId = $nextId[0];
        
        //$parent->setId($this->identity($nextId['nextID']));
        
        foreach($parent->getI18ns() as $i18n) {
            //$model = new $this->model();
            
            $i18n->setAction('insert');
            $i18n->setProductVariationId($parent->getId());            
            //var_dump($i18n);
            
            //$return[] = $model;
        }
        
        return parent::push($parent->getI18ns(),$dbObj);
    }
}