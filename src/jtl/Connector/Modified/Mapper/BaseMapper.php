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
	protected $type;
	protected $model;
	protected $sqlite = null;
    
	public function __construct() {
	    $session = new SessionHelper("modified");
	    $reflect = new \ReflectionClass($this);
	    
	    $this->db = Mysql::getInstance();
	    $this->shopConfig = $session->shopConfig;
	    $this->model = "\\jtl\\Connector\\Model\\".$reflect->getShortName();
	    $this->type = null; 
	}
	
	/**
	 * Generate model from db data
	 * @param array $data
	 * @return object
	 */
	public function generateModel($data) {
	    $model = new $this->model();
		if(!$this->type) $this->type = $model->getModelType();

		foreach($this->mapperConfig['mapPull'] as $host => $endpoint) {
		    $value = null;
		    
		    if($this->type->getProperty($host)->isNavigation()) {
		        list($endpoint,$setMethod) = explode('|',$endpoint);
		        
		        $subMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\".$endpoint;
		        
		        if(!class_exists($subMapperClass)) throw new \Exception("There is no mapper for ".$endpoint);
		        else {
		            if(!method_exists($model,$setMethod)) throw new \Exception("Set method ".$setMethod." does not exists");
		        
		            $subMapper = new $subMapperClass();
		        
		            $values = $subMapper->pull($data);
		        
		            foreach($values as $obj) $model->$setMethod($obj);		            
		        }
		    }
		    else {
		        if(isset($data[$endpoint])) $value = $data[$endpoint];
		        elseif(method_exists(get_class($this),$host)) $value = $this->$host($data);
		        else throw new \Exception("There is no property or method to map ".$host);

		        if($this->type->getProperty($host)->isIdentity()) $value = new Identity($value);
		        else {
		            $type = $this->type->getProperty($host)->getType();
		            
		            if($type == "DateTime" && !is_null($value)) $value = new \DateTime($value);
		            else settype($value,$type);		            
		        }
		        
		        $setMethod = 'set'.ucfirst($host);
		        $model->$setMethod($value);
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
	public function pull($data=null,$offset=0,$limit) {        
        $limitQuery = isset($limit) ? ' LIMIT '.$offset.','.$limit : '';
        
	    if(isset($this->mapperConfig['query'])) {
	        $query = !is_null($data) ? preg_replace('/\[\[(\w+)\]\]/e','$data[$1]', $this->mapperConfig['query']) : $this->mapperConfig['query'];
	        $query .= $limitQuery;	        
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
    
	/**
	 * Get sqlite instance of setup db
	 * @return \PDO 
	 */
	public function getSqlite() {
	    if(is_null($this->sqlite)) $this->sqlite = new \PDO('sqlite:'.realpath(__DIR__.'/../../Modified/').'/connector.sdb');
	    
	    return $this->sqlite;
	}
}