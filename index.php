<?php
require_once __DIR__."/vendor/autoload.php";

use \jtl\Connector\Application\Application;
use \jtl\Connector\Modified\Modified;

defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);

if (!strpos($_SERVER['REQUEST_URI'], 'install')) {
    $connector = Modified::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
}
