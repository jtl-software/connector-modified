<?php
defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);
defined("CONNECTOR_VERSION") || define("CONNECTOR_VERSION", file_get_contents(__DIR__.'/version'));

$loader = require_once __DIR__."/vendor/autoload.php";
$loader->add('', CONNECTOR_DIR . '/plugins');

use \jtl\Connector\Application\Application;
use \jtl\Connector\Modified\Modified;

if (!strpos($_SERVER['REQUEST_URI'], 'install')) {
    $connector = Modified::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
}
