<?php
namespace jtl\Connector\Modified\Mapper;

use \jtl\Core\Database\Mysql;
use \jtl\Connector\Session\SessionHelper;
use \jtl\Core\Utilities\Language;
use \jtl\Connector\Model\Identity;

class BaseMapper
{
    protected $db;
    protected $mapperConfig;
	protected $shopConfig;
    
	public function __construct() {
	    $this->db = Mysql::getInstance();
	     
	    $session = new SessionHelper("modified");
	    
	    $this->shopConfig = $session->shopConfig;
	}
	
	/**
	 * Generate model from db data
	 * @param array $data
	 * @return object
	 */
	public function generateModel($data) {
	    $reflect = new \ReflectionClass($this);
	    
	    $class = "\\jtl\\Connector\\Model\\{$reflect->getShortName()}";
		$model = new $class();
		
		foreach($this->mapperConfig['mapPull'] as $host => $endpoint) {
		    $value = null;
		    $endpoint = explode('|',$endpoint);
		    
		    if(isset($data[$endpoint[0]])) $value = $data[$endpoint[0]];
		    elseif(method_exists(get_class($this),$host)) $value = $this->$host($data);
		    else {
		        $subMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\{$endpoint[0]}";
		        if(!$isSub = class_exists($subMapperClass)) throw new \Exception("There was no property or method to map ".$endpoint[0]);
		        else {
		            if(!method_exists($model,$endpoint[1])) throw new \Exception("Set method ".$setmethod." does not exists");
		            
		            $subMapper = new $subMapperClass();
		            
		            $values = $subMapper->pull($data);
		            
		            foreach($values as $obj) $model->$endpoint[1]($obj);
		        }
		    }		   		
		   		   
		    if(!$isSub) {
		        if($model->isIdentity($host)) $value = new Identity($value);
		        if(isset($endpoint[1])) settype($value,$endpoint[1]);
		        
		        $setMethod =  'set'.ucfirst($host);
		        $model->{$setMethod}($value);
		    }
		}
		
		return $model->getPublic();
	}
	
	/**
	 * map from model to db object
	 * @param object $data
	 * @return \stdClass
	 */
	public function generateDbObj($data) {
		$dbObj = new \stdClass();

		foreach($this->mapperConfig['mapPush'] as $endpoint => $host) {
			if(!empty($endpoint)) $dbObj->$endpoint = isset($data->$host) ? $data->$host : null;
			if(method_exists(get_class($this),$endpoint)) $dbObj->$endpoint = $this->$endpoint($data);
		}
		
		return $dbObj;
	}
	
	/**
	 * Default pull method
	 * @param array $data
	 * @param integer $offset
	 * @param integer $limit
	 * @return array
	 */  
	public function pull($data,$offset=0,$limit) {        
        $limitQuery = isset($limit) ? ' LIMIT '.$offset.','.$limit : '';
        
	    if(isset($this->mapperConfig['query'])) {
	        $query = preg_replace('/\[\[(\w+)\]\]/e','$data[$1]', $this->mapperConfig['query']).$limitQuery;	        
	    }
	    else $query = 'SELECT * FROM '.$this->mapperConfig['table'].$limitQuery;
        
	    $dbResult = $this->db->query($query);        	

	    $return = array();
		
		foreach($dbResult as $data) {			
			$return[] = $this->generateModel($data);			            	
		}		
			    
		return $return;
	}
    
	/**
	 * Get full locale by ISO code
	 * @param string $country
	 * @return Ambigous <NULL, multitype:string , string>
	 */
	public function fullLocale($country) {
	    return Language::map(null,$country);
	}
	
	/**
	 * get modified language id by ISO code
	 * @param string $locale
	 */
	public function locale2id($locale) {
	    $iso2 = Language::map($locale);
	    
	    $dbResult = $this->db->query('SELECT languages_id FROM languages WHERE code="'.$iso2.'"');

	    return $dbResult[0]['languages_id'];
	}
	
	/**
	 * Replace 0 value with empty string
	 * @param  $data
	 * @return Ambigous <string, unknown>
	 */
	public function replaceZero($data) {
	    return ($data == 0) ? '' : $data;
	}
	
	/**
	 * Default statistics
	 * @return number
	 */
	public function statistic() {	    	
	    $objs = $this->db->query("SELECT count(*) as count FROM {$this->mapperConfig['table']} LIMIT 1", array("return" => "object"));
	    
	    return $objs !== null ? intval($objs[0]->count) : 0;
	}	
}