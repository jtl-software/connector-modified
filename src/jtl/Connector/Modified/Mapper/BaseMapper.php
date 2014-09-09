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
		        //else throw new \Exception($this->model.": There is no property or method to map ".$host);
		        else $value = '';

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
		
		if(method_exists(get_class($this),'addData')) $this->addData($model,$data);
		
		return $this->type->isMain() ? $model->getPublic() : $model;
	}
	
    /**
     * map from model to db object
     * @param unknown $data
     * @param string $parentDbObj
     * @throws \Exception
     * @return multitype:NULL
     */
	public function generateDbObj($data,$parentDbObj,$parentObj=null,$addToParent=false) {
	    $return = [];
	    if(!is_array($data)) $data = array($data);
	    
	    foreach($data as $obj) {
	        $subMapper = [];
	        
    	    $model = new $this->model();	
    	        
    	    if(!$this->type) $this->type = $model->getModelType();
    	    
    	    $dbObj = new \stdClass();
    
    		foreach($this->mapperConfig['mapPush'] as $endpoint => $host) {
    		    if(is_null($host) && method_exists(get_class($this),$endpoint)) {
    		        $dbObj->$endpoint = $this->$endpoint($obj,$model,$parentObj);
    		    }
    		    elseif($this->type->getProperty($host)->isNavigation()) {
    		        list($preEndpoint,$preNavSetMethod,$preMapper) = explode('|',$endpoint);
    		        
    		        if($preMapper) {
    		            $preSubMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\".$preEndpoint;
    		            
    		            if(!class_exists($preSubMapperClass)) throw new \Exception("There is no mapper for ".$host);
    		            else {
    		                $preSubMapper = new $preSubMapperClass();
    		            
    		                $values = $preSubMapper->push($obj,$dbObj);
    		            
    		                foreach($values as $setObj) $model->$preNavSetMethod($setObj);
    		            }
    		        }
    		        else $subMapper[$endpoint] = $host;    		       
    		    }
    		    else {
    		        $value = null;
    		        
		            $getMethod = 'get'.ucfirst($host);
		            $setMethod = 'set'.ucfirst($host);
                    $value = $obj->$getMethod();
        		    
        		    if(isset($value)) {
        		        if($this->type->getProperty($host)->isIdentity()) {
        		            $model->$setMethod($value);
        		            
        		            $value = $value->getEndpoint();
        		        }
        		        else {
        		            $type = $this->type->getProperty($host)->getType();
        		            if($type == "DateTime") $value = $value->format('Y-m-d H:i:s');
        		            elseif($type == "bool") settype($value,"integer");
        		        }		       
        		    }
        		    else throw new \Exception("There is no property or method to map ".$endpoint);
        		            		    
        		    if(!empty($value)) $dbObj->$endpoint = $value;        		    
    		    }	    		    
    		}
            
    		if(!$addToParent) {
                
    		    switch($obj->getAction()) {
        		    case 'complete':
        		        if(isset($this->mapperConfig['where'])) {
        		            $whereKey = $this->mapperConfig['where'];
        		            $whereValue = $dbObj->{$this->mapperConfig['where']};
        		        
        		            if(is_array($whereKey)) {
        		                $whereValue = [];
        		                foreach($whereKey as $key) {
        		                    $whereValue[] = $dbObj->{$key};
        		                }
        		            }
        		        
        		            $insertResult = $this->db->deleteInsertRow($dbObj,$this->mapperConfig['table'],$whereKey,$whereValue);
        		            
        		            if(isset($this->mapperConfig['identity'])) {
        		                $obj->{$this->mapperConfig['identity']}()->setEndpoint($insertResult->getKey());
        		            }
        		        }
        		    break;
        		
        		    case 'insert':
        		        $insertResult = $this->db->insertRow($dbObj,$this->mapperConfig['table']);
        		        
        		        if(isset($this->mapperConfig['identity'])) {
        		            $obj->{$this->mapperConfig['identity']}()->setEndpoint($insertResult->getKey());    		            
        		        }
        		    break;
        		
        		    case 'update':
        		        if(isset($this->mapperConfig['where'])) {
            		        $whereKey = $this->mapperConfig['where'];
            		        $whereValue = $dbObj->{$this->mapperConfig['where']};
            		
            		        if(is_array($whereKey)) {
            		            $whereValue = [];
            		            foreach($whereKey as $key) {
            		                $whereValue[] = $dbObj->{$key};
            		            }
            		        }
            		        
            		        $this->db->updateRow($dbObj,$this->mapperConfig['table'],$whereKey,$whereValue);
        		        }
        		    break;
        		
        		    case 'delete':
        		        if(isset($this->mapperConfig['where'])) {
        		            $whereKey = $this->mapperConfig['where'];
        		            $whereValue = $dbObj->{$this->mapperConfig['where']};
        		        
        		            if(is_array($whereKey)) {
        		                $whereValue = [];
        		                foreach($whereKey as $key) {
        		                    $whereValue[] = $dbObj->{$key};
        		                }
        		            }
        		        
        		            $this->db->deleteRow($dbObj,$this->mapperConfig['table'],$whereKey,$whereValue);
        		        }        		   
        		    break;
        		}
        		
        		//if($obj->getAction()) var_dump($dbObj);
    		}
    		else {	
    		    foreach($dbObj as $key => $value) {
    		        $parentDbObj->$key = $value;
    		    }  	    
    		}
            
    		// sub mapper
		    foreach($subMapper as $endpoint => $host) {
		        list($endpoint,$navSetMethod) = explode('|',$endpoint);
		        
		        $subMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\".$endpoint;
		        
		        if(!class_exists($subMapperClass)) throw new \Exception("There is no mapper for ".$host);
		        else {
		            $subMapper = new $subMapperClass();
		        
		            $values = $subMapper->push($obj);
		        
		            foreach($values as $setObj) $model->$navSetMethod($setObj);
		        }
		    }
		    
            $return[] = $model->getPublic();		
	    }
		
	    return is_array($data) ? $return : $return[0];
	}
	
	/**
	 * Default pull method
	 * @param array $data
	 * @param integer $offset
	 * @param integer $limit
	 * @return array
	 */  
	public function pull($parentData=null,$offset=0,$limit) {        
        $limitQuery = isset($limit) ? ' LIMIT '.$offset.','.$limit : '';
        
	    if(isset($this->mapperConfig['query'])) {
	        $query = !is_null($parentData) ? preg_replace('/\[\[(\w+)\]\]/e','$parentData[$1]', $this->mapperConfig['query']) : $this->mapperConfig['query'];
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
	 * Default push method
	 * @param unknown $data
	 * @param string $dbObj
	 * @return multitype:NULL
	 */
	public function push($data,$dbObj=null) {
	    if($data->getAction() == 'complete' && method_exists(get_class($this),'complete')) $this->complete($data);	    
	    
	    if(isset($this->mapperConfig['getMethod'])) {
	        $subGetMethod = $this->mapperConfig['getMethod'];
	        $parent = $data;
	        $data = $data->$subGetMethod();	       
	    }
	    
	    $return = $this->generateDbObj($data,$dbObj,$parent);
	    
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
	 * get full locale by modified language id
	 * @param unknown $id
	 * @return \jtl\Connector\Modified\Mapper\Ambigous
	 */
	public function id2locale($id) {
	    $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id="'.$id.'"');
	    
	    return $this->fullLocale($dbResult[0]['code']);
	}
	
	/**
	 * Replace 0 value with empty string
	 * @param $data
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
	
	/**
	 * Get modified customer groups
	 * @return Ambigous <NULL, number, boolean, multitype:multitype: , multitype:unknown >
	 */
	public function getCustomerGroups() {
	    return $this->db->query("SELECT customers_status_id FROM customers_status GROUP BY customers_status_id ORDER BY customers_status_id");
	}
	
	/**
	 * Create a new identity
	 * @param int $id
	 * @return \jtl\Connector\Model\Identity
	 */
	public function identity($id) {
	    return new Identity($id);
	}
}