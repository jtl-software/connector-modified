<?php
namespace jtl\Connector\Modified;

use \jtl\Core\Rpc\RequestPacket;
use \jtl\Core\Utilities\RpcMethod;
use \jtl\Core\Database\Mysql;
use \jtl\Core\Rpc\ResponsePacket;
use \jtl\Connector\Session\SessionHelper;
use \jtl\Connector\Base\Connector as BaseConnector;
use \jtl\Connector\Modified\Config\Loader\Config as ConfigLoader;
use \jtl\Core\Rpc\Error as Error;
use \jtl\Core\Http\Response;

class Modified extends BaseConnector
{
    protected $_controller;
    protected $_action;
	
    protected function init()
    {
        $session = new SessionHelper("modified");
        
        set_error_handler(array($this,'error_handler'), E_ALL);
        set_exception_handler(array($this,'exception_handler'));
        register_shutdown_function(array($this,'shutdown_handler'));
        
        $config = $this->getConfig();
        $session->config = $config;
        
        // read modified configuration
        if (!$config->existsLoaderByName('ModifiedConfig')) {
           	$config->addLoader(new ConfigLoader($config->read('connector_root') . '/includes/configure.php'));
        }
		
		// read db params from config
        $dbconfig = $config->read("db");
        
		// get db singleton and connect
        $db = Mysql::getInstance();        
        
        if (!$db->isConnected()) {
            $db->connect(array(
            	"host" => $dbconfig["host"], 
            	"user" => $dbconfig["user"], 
            	"password" => $dbconfig["pass"], 
            	"name" => $dbconfig["name"]
			));
        }		      
              
        $db->setNames();
		
		// read modified in-shop configuration from db
		$shopConfig = $db->query("SElECT configuration_key,configuration_value FROM configuration");
		
		foreach($shopConfig as $entry) {
			$configArray[$entry['configuration_key']] = $entry['configuration_value'] == 'true' ? 1 : ($entry['configuration_value'] == 'false' ? 0 : $entry['configuration_value']);
		}
		
		$configArray['img'] = $config->read("img");
		$configArray['shop'] = $config->read("shop");
		$configArray['shop']['fullUrl'] = $configArray['shop']['url'].$configArray['shop']['folder'];
				
		$session->shopConfig = $configArray;
    }
	
    public function canHandle()
    {
        $controller = RpcMethod::buildController($this->getMethod()->getController());
        $class = "\\jtl\\Connector\\Modified\\Controller\\{$controller}";
        
        if(class_exists($class)) {       
            $this->_controller = $class::getInstance();
            $this->_action = RpcMethod::buildAction($this->getMethod()->getAction());
            
            return is_callable(array($this->_controller, $this->_action));
        }
        
        return false;
    }

    public function handle(RequestPacket $requestpacket)
    {
       	$this->init();
       	$this->_controller->setMethod($this->getMethod());

        return $this->_controller->{$this->_action}($requestpacket->getParams());        
    }
    
    function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $types = array(
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_CORE_ERROR => 'E_COMPILE_ERROR',
            E_CORE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        );
    
        file_put_contents("/tmp/error.log", date("[Y-m-d H:i:s] ") . "(" . $types[$errno] . ") File ({$errfile}, {$errline}): {$errstr}\n", FILE_APPEND);
    }

    public function exception_handler(\Exception $exception)
    {
        $trace = $exception->getTrace();
        if (isset($trace[0]['args'][0])) {
            $requestpacket = $trace[0]['args'][0];
        }
    
        $error = new Error();
        $error->setCode($exception->getCode())
            ->setData("Exception: " . substr(strrchr(get_class($exception), "\\"), 1) . " - File: {$exception->getFile()} - Line: {$exception->getLine()}")
            ->setMessage($exception->getMessage());
    
        $responsepacket = new ResponsePacket();
        $responsepacket->setError($error)
            ->setJtlrpc("2.0");
    
        if (isset($requestpacket) && $requestpacket !== null && is_object($requestpacket) && get_class($requestpacket) == "jtl\\Core\\Rpc\\RequestPacket") {
            $responsepacket->setId($requestpacket->getId());
        }
    
        Response::send($responsepacket);
    }
    
    public function shutdown_handler()
    {
        if(($err = error_get_last())) {
            if($err['type'] != 2 && $err['type'] != 8) {
                ob_clean();
    
                $error = new Error();
                $error->setCode($err['type'])
                    ->setData('Shutdown! File: ' . $err['file'] . ' - Line: ' . $err['line'])
                    ->setMessage($err['message']);
        
                $responsepacket = new ResponsePacket();
                $responsepacket->setError($error)
                    ->setJtlrpc("2.0");
        
                Response::send($responsepacket);
            }            
        }        
    }
}
?>
