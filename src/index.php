<?php
define("APP_DIR", __DIR__);

require_once (__DIR__ . "/../vendor/autoload.php");

use \jtl\Connector\Application\Application;
use \jtl\Connector\Core\Rpc\RequestPacket;
use \jtl\Connector\Core\Rpc\ResponsePacket;
use \jtl\Connector\Core\Rpc\Error;
use \jtl\Connector\Core\Http\Response;
use \jtl\Connector\Modified\Modified;

//error_reporting(E_ALL ^ E_NOTICE);
//ini_set('display_errors',1);

$condir = __DIR__ . '/../vendor/jtl/connector/';
define('CONNECTOR_DIR', $condir);

$connector = Modified::getInstance();
$application = Application::getInstance();
$application->register($connector);
$application->run();