<?php
require_once (__DIR__ . "/vendor/autoload.php");

use \jtl\Connector\Application\Application;
use \jtl\Connector\Core\Rpc\RequestPacket;
use \jtl\Connector\Core\Rpc\ResponsePacket;
use \jtl\Connector\Core\Rpc\Error;
use \jtl\Connector\Core\Http\Response;
use \jtl\Connector\Modified\Modified;

define('CONNECTOR_DIR',__DIR__);

$connector = Modified::getInstance();
$application = Application::getInstance();
$application->register($connector);
$application->run();