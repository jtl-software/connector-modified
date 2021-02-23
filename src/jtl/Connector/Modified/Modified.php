<?php

namespace jtl\Connector\Modified;

use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Base\Connector as BaseConnector;
use jtl\Connector\Core\Rpc\Method;
use jtl\Connector\Modified\Mapper\PrimaryKeyMapper;
use jtl\Connector\Result\Action;
use jtl\Connector\Modified\Auth\TokenLoader;
use jtl\Connector\Modified\Checksum\ChecksumLoader;

class Modified extends BaseConnector
{
    protected $controller;
    protected $action;

    protected $shopConfig;
    protected $connectorConfig;

    public function initialize()
    {
        if (!isset($this->shopConfig)) {
            $this->shopConfig = $this->readConfigFile();
        }
        if (!isset($this->connectorConfig)) {
            $this->connectorConfig = json_decode(@file_get_contents(CONNECTOR_DIR . '/config/config.json'));
        }

        $db = Mysql::getInstance();

        if (!$db->isConnected()) {
            $db->connect([
                "host" => $this->shopConfig['db']["host"],
                "user" => $this->shopConfig['db']["user"],
                "password" => $this->shopConfig['db']["pass"],
                "name" => $this->shopConfig['db']["name"]
            ]);
        }

        if (isset($this->connectorConfig->utf8) && $this->connectorConfig->utf8 !== '0') {
            $db->setNames();
            $db->setCharset();
        }

        if (!isset($this->shopConfig['settings'])) {
            $this->shopConfig += $this->readConfigDb($db);
        }

        $this->update($db);

        $this->setPrimaryKeyMapper(new PrimaryKeyMapper());
        $this->setTokenLoader(new TokenLoader());
        $this->setChecksumLoader(new ChecksumLoader());
    }


    private function readConfigFile()
    {

        require_once(CONNECTOR_DIR . '/../includes/configure.php');
        require_once(CONNECTOR_DIR . '/../inc/set_admin_directory.inc.php');

        if (defined('DIR_ADMIN')) {
            require_once(CONNECTOR_DIR . '/../' . DIR_ADMIN . '/includes/version.php');
        } else {
            require_once(CONNECTOR_DIR . '/../admin/includes/version.php');
        }


        return [
            'shop' => [
                'url' => HTTP_SERVER,
                'folder' => DIR_WS_CATALOG,
                'path' => DIR_FS_DOCUMENT_ROOT,
                'fullUrl' => HTTP_SERVER . DIR_WS_CATALOG
            ],
            'db' => [
                'host' => DB_SERVER,
                'name' => DB_DATABASE,
                'user' => DB_SERVER_USERNAME,
                'pass' => DB_SERVER_PASSWORD,
                'version' => ltrim(DB_VERSION, 'MOD_')
            ],
            'img' => [
                'original' => DIR_WS_ORIGINAL_IMAGES,
                'thumbnails' => DIR_WS_THUMBNAIL_IMAGES,
                'info' => DIR_WS_INFO_IMAGES,
                'popup' => DIR_WS_POPUP_IMAGES
            ]
        ];
    }

    private function readConfigDb($db)
    {
        $configDb = $db->query("SElECT configuration_key,configuration_value FROM configuration");

        $return = [];

        foreach ($configDb as $entry) {
            $return[$entry['configuration_key']] = $entry['configuration_value'] == 'true' ? 1 : ($entry['configuration_value'] == 'false' ? 0 : $entry['configuration_value']);
        }

        return [
            'settings' => $return
        ];
    }

    private function update($db)
    {
        if (version_compare(file_get_contents(CONNECTOR_DIR . '/db/version'), CONNECTOR_VERSION) == -1) {
            foreach (new \DirectoryIterator(CONNECTOR_DIR . '/db/updates') as $updateFile) {
                if ($updateFile->isDot()) {
                    continue;
                }

                if (version_compare(file_get_contents(CONNECTOR_DIR . '/db/version'), $updateFile->getBasename('.php')) == -1) {
                    include(CONNECTOR_DIR . '/db/updates/' . $updateFile);
                }
            }
        }
    }

    public function canHandle()
    {
        $controllers = [
            'Category',
            'CrossSelling',
            'Customer',
            'CustomerOrder',
            'GlobalData',
            'Image',
            'Manufacturer',
            'Payment',
            'Product',
            'ProductPrice',
            'ProductStockLevel',
            'StatusChange',
        ];

        $controllerName = RpcMethod::buildController($this->getMethod()->getController());

        $controllerClass = sprintf('jtl\\Connector\\Modified\\Controller\\%s', $controllerName);
        $db = Mysql::getInstance();
        $this->controller = null;
        if (class_exists($controllerClass)) {
            $this->controller = new $controllerClass($db, $this->shopConfig, $this->connectorConfig);
        } elseif (in_array($controllerName, $controllers, true)) {
            $this->controller = new Controller($db, $this->shopConfig, $this->connectorConfig, $controllerName);
        }

        if (!is_null($this->controller)) {
            $this->action = RpcMethod::buildAction($this->getMethod()->getAction());
            return is_callable([$this->controller, $this->action]);
        }

        return false;
    }

    public function handle(RequestPacket $requestpacket)
    {
        $this->controller->setMethod($this->getMethod());

        $result = [];

        if ($this->action === Method::ACTION_PUSH || $this->action === Method::ACTION_DELETE) {
            if (!is_array($requestpacket->getParams())) {
                throw new \Exception('data is not an array');
            }

            $action = new Action();
            $results = [];
            $errors = [];

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
