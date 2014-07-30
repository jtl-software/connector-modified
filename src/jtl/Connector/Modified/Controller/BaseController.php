<?php
namespace jtl\Connector\Modified\Controller;

use \jtl\Core\Controller\Controller;
use \jtl\Core\Database\Mysql;
use \jtl\Connector\Result\Action;
use \jtl\Core\Model\QueryFilter;
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
        
        $filter = new QueryFilter();
        $filter->set($params);

        try {
            $reflect = new \ReflectionClass($this);
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";
            
            if(!class_exists($class)) throw new \Exception("Class ".$class." not available"); 
            
            $mapper = new $class();
        
            $result = $mapper->pull(null,$filter->getOffset(),$filter->getLimit());
            	
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
        $action->setResult(true);
        
        return $action;        
    }
    
    public function delete($params) 
    {
        
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
                
                $statModel->_available = $mapper->statistic();                
                $statModel->_pending = 0;   
                $statModel->_controllerName = lcfirst($reflect->getShortName());
                
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