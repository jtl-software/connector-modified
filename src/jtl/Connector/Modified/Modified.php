<?php
namespace jtl\Connector\Modified;

use \jtl\Connector\Core\Rpc\RequestPacket;
use \jtl\Connector\Core\Utilities\RpcMethod;
use \jtl\Connector\Core\Database\Mysql;
use \jtl\Connector\Core\Rpc\ResponsePacket;
use \jtl\Connector\Session\SessionHelper;
use \jtl\Connector\Base\Connector as BaseConnector;
use \jtl\Connector\Core\Rpc\Error as Error;
use \jtl\Connector\Core\Http\Response;
use \jtl\Connector\Core\Rpc\Method;
use \jtl\Connector\Modified\Mapper\PrimaryKeyMapper;
use \jtl\Connector\Core\Config\Config;
use \jtl\Connector\Core\Config\Loader\Json as ConfigJson;
use \jtl\Connector\Core\Config\Loader\System as ConfigSystem;
use \jtl\Connector\Result\Action;
use \jtl\Connector\Modified\Auth\TokenLoader;
use \jtl\Connector\Modified\Checksum\ChecksumLoader;
use \jtl\Connector\Core\Logger\Logger;

class Modified extends BaseConnector
{
    protected $controller;
    protected $action;

    public function initialize()
    {
        $this->initConnectorConfig();

        $session = new SessionHelper("modified");

        if (!isset($session->shopConfig)) {
            $session->shopConfig = $this->readConfigFile();
        }
        if (!isset($session->connectorConfig)) {
            $session->connectorConfig = json_decode(@file_get_contents(CONNECTOR_DIR.'/config/config.json'));
        }

        $db = Mysql::getInstance();

        if (!$db->isConnected()) {
            $db->connect(array(
                "host" => $session->shopConfig['db']["host"],
                "user" => $session->shopConfig['db']["user"],
                "password" => $session->shopConfig['db']["pass"],
                "name" => $session->shopConfig['db']["name"]
            ));
        }

        //$db->setNames();

        if (!isset($session->shopConfig['settings'])) {
            $session->shopConfig += $this->readConfigDb($db);
        }

        $this->setPrimaryKeyMapper(new PrimaryKeyMapper());
        $this->setTokenLoader(new TokenLoader());
        $this->setChecksumLoader(new ChecksumLoader());
    }

    protected function initConnectorConfig()
    {
        $config = null;

        if (isset($_SESSION['config'])) {
            $config = $_SESSION['config'];
        }

        if (empty($config)) {
            if (!is_null($this->config)) {
                $config = $this->getConfig();
            }

            if (empty($config)) {
                $json = new ConfigJson(CONNECTOR_DIR . '/config/config.json');
                $config = new Config(array(
                    $json,
                    new ConfigSystem()
                ));
            }
        }

        if (!isset($_SESSION['config'])) {
            $_SESSION['config'] = $config;
        }

        $this->setConfig($config);
    }

    private function readConfigFile()
    {
        require_once(CONNECTOR_DIR.'/../includes/configure.php');

        return array(
            'shop' => array(
                'url' => HTTP_SERVER,
                'folder' => DIR_WS_CATALOG,
                'path' => DIR_FS_DOCUMENT_ROOT,
                'fullUrl' => HTTP_SERVER.DIR_WS_CATALOG
            ),
            'db' => array(
                'host' => DB_SERVER,
                'name' => DB_DATABASE,
                'user' => DB_SERVER_USERNAME,
                'pass' => DB_SERVER_PASSWORD
            ),
            'img' => array(
                'original' => DIR_WS_ORIGINAL_IMAGES,
                'thumbnails' => DIR_WS_THUMBNAIL_IMAGES,
                'info' => DIR_WS_INFO_IMAGES,
                'popup' => DIR_WS_POPUP_IMAGES
            )
        );
    }

    private function readConfigDb($db)
    {
        $configDb = $db->query("SElECT configuration_key,configuration_value FROM configuration");

        $return = array();

        foreach ($configDb as $entry) {
            $return[$entry['configuration_key']] = $entry['configuration_value'] == 'true' ? 1 : ($entry['configuration_value'] == 'false' ? 0 : $entry['configuration_value']);
        }

        return array(
            'settings' => $return
        );
    }

    public function canHandle()
    {
        $controller = RpcMethod::buildController($this->getMethod()->getController());
        $class = "\\jtl\\Connector\\Modified\\Controller\\{$controller}";

        if (class_exists($class)) {
            $this->controller = $class::getInstance();
            $this->action = RpcMethod::buildAction($this->getMethod()->getAction());

            return is_callable(array($this->controller, $this->action));
        }

        return false;
    }

    public function handle(RequestPacket $requestpacket)
    {
        $this->controller->setMethod($this->getMethod());

        $result = array();

        if ($this->action === Method::ACTION_PUSH || $this->action === Method::ACTION_DELETE) {
            if (!is_array($requestpacket->getParams())) {
                throw new \Exception('data is not an array');
            }

            $action = new Action();
            $results = array();
            $errors = array();

            foreach ($requestpacket->getParams() as $param) {
                $result = $this->controller->{$this->action}($param);
                $results[] = $result->getResult();
            }

            $action->setHandled(true)
                ->setResult($results)
                ->setError($result->getError());

            return $action;
        } else {
            return $this->controller->{$this->action}($requestpacket->getParams());
        }
    }
}
