<?php
defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);

$loader = require_once __DIR__."/vendor/autoload.php";
$loader->add('', CONNECTOR_DIR . '/plugins');

$config = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/build-config.yaml');
defined('CONNECTOR_VERSION') || define('CONNECTOR_VERSION', $config['version']);



use \jtl\Connector\Application\Application;
use \jtl\Connector\Modified\Modified;

if (!strpos($_SERVER['REQUEST_URI'], 'install')) {
    $connector = Modified::getInstance();
    $application = Application::getInstance();
    $application->register($connector);
    $application->run();
}
