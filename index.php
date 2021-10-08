<?php
use \jtl\Connector\Application\Application;
use \jtl\Connector\Modified\Connector;
use Symfony\Component\Yaml\Yaml;

defined('CONNECTOR_DIR') || define("CONNECTOR_DIR", __DIR__);

$loader = require_once __DIR__."/vendor/autoload.php";
$loader->add('', CONNECTOR_DIR . '/plugins');

$config = Yaml::parseFile(__DIR__ . '/build-config.yaml');
defined('CONNECTOR_VERSION') || define('CONNECTOR_VERSION', $config['version']);
if (!strpos($_SERVER['REQUEST_URI'], 'install')) {
    $connector = Connector::getInstance();
    /** @var Application $application */
    $application = Application::getInstance();
    $application->createFeaturesFileIfNecessary(sprintf('%s/config/features.json.example', CONNECTOR_DIR));
    $application->register($connector);
    $application->run();
}
