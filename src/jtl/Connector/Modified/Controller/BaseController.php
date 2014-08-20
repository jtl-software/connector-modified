<?php
namespace jtl\Connector\Modified\Controller;

use \jtl\Core\Controller\Controller;
use \jtl\Core\Database\Mysql;
use \jtl\Connector\Result\Action;
use \jtl\Core\Rpc\Error;
use \jtl\Core\Utilities\ClassName;
use \jtl\Connector\Model\Statistic;

class BaseController extends Controller
{
   	protected $_db;
		
	public function __construct() {
		 $this->_db = Mysql::getInstance();		 
	}	
	
    public function pull($params) {        
        $action = new Action();
        $action->setHandled(true);
       
        try {
            $reflect = new \ReflectionClass($this);
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";
            
            if(!class_exists($class)) throw new \Exception("Class ".$class." not available"); 
            
            $mapper = new $class();
        
            $result = $mapper->pull(null,$params->getOffset(),$params->getLimit());
            	
            $action->setResult($result);
        }
        catch (\Exception $exc) {
            $err = new Error();
            $err->setCode($exc->getCode());
            $err->setMessage($exc->getFile().' ('.$exc->getLine().'):'.$exc->getMessage());
            $action->setError($err);
        }
        
        return $action;        
    }
	
    public function push($params) {
        $action = new Action();
        
        $action->setHandled(true);
        
        try {
            $reflect = new \ReflectionClass($this);
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";
            
            if(!class_exists($class)) throw new \Exception("Class ".$class." not available");
            
            $mapper = new $class();
            
            $result = $mapper->push($params);
            
            $action->setResult($result);
        }
        catch (\Exception $exc) {
            $err = new Error();
            $err->setCode($exc->getCode());
            $err->setMessage($exc->getFile().' ('.$exc->getLine().'):'.$exc->getMessage());
            $action->setError($err);
        }
        
        return $action;        
    }
    
    public function delete($params) 
    {
        // not used anymore   
    }
    
    public function statistic($params) {
        $reflect = new \ReflectionClass($this);
        $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";
        
        if(class_exists($class)) {
            $action = new Action();
            $action->setHandled(true);
    
            try {
                $mapper = new $class();
                
                $statModel = new Statistic();
                
                $statModel->setAvailable($mapper->statistic());                
                $statModel->setPending(0);   
                $statModel->setControllerName(lcfirst($reflect->getShortName()));
                
                $action->setResult($statModel->getPublic());
            }
            catch (\Exception $exc) {
                $err = new Error();
                $err->setCode($exc->getCode());
                $err->setMessage($exc->getMessage());
                $action->setError($err);
            }
            
            return $action;
        }
    }	
	
}