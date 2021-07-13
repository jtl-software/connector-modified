<?php
namespace jtl\Connector\Modified;

use jtl\Connector\Core\Exception\DatabaseException;
use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Modified\Controller\SharedController;
use jtl\Connector\Session\SessionHelper;
use jtl\Connector\Base\Connector as BaseConnector;
use jtl\Connector\Core\Rpc\Method;
use jtl\Connector\Modified\Mapper\PrimaryKeyMapper;
use jtl\Connector\Result\Action;
use jtl\Connector\Modified\Auth\TokenLoader;
use jtl\Connector\Modified\Checksum\ChecksumLoader;

class Modified extends BaseConnector
{
    public const
        SESSION_NAMESPACE = 'modified';

    /**
     * @var SessionHelper|null
     */
    protected static $sessionHelper = null;

    /**
     * @var null
     */
    protected $controller = null;

    /**
     * @var null
     */
    protected $action = null;

    /**
     * @var array
     */
    protected $shopConfig = [];

    /**
     * @var \stdClass
     */
    protected $connectorConfig;

    /**
     * @throws DatabaseException
     */
    public function initialize()
    {
        $this->shopConfig = $this->readConfigFile();
        $this->connectorConfig = json_decode(@file_get_contents(CONNECTOR_DIR.'/config/config.json'));

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

    /**
     * @param Mysql $db
     * @return array[]
     */
    private function readConfigDb(Mysql $db): array
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

    /**
     * @param Mysql $db
     */
    private function update(Mysql $db): void
    {
        if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), CONNECTOR_VERSION) == -1) {
            $versions = [];
            foreach (new \DirectoryIterator(CONNECTOR_DIR.'/db/updates') as $item) {
                if ($item->isFile()) {
                    $versions[] = $item->getBasename('.php');
                }
            }

            sort($versions);

            foreach ($versions as $version) {
                if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), $version) == -1) {
                    include(CONNECTOR_DIR.'/db/updates/' . $version . '.php');
                    file_put_contents(CONNECTOR_DIR.'/db/version', $version);
                }
            }
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
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
        $db = Mysql::getInstance();

        $controllerClass = sprintf('jtl\\Connector\\Modified\\Controller\\%s', $controllerName);

        if (class_exists($controllerClass)) {
            $this->controller = new $controllerClass($db, $this->shopConfig, $this->connectorConfig);
        } elseif (in_array($controllerName, $controllers, true)) {
            $this->controller = new SharedController($db, $this->shopConfig, $this->connectorConfig, $controllerName);
        }

        if (!is_null($this->controller)) {
            $this->action = RpcMethod::buildAction($this->getMethod()->getAction());
            return is_callable([$this->controller, $this->action]);
        }

        return false;
    }

    /**
     * @param RequestPacket $requestpacket
     * @return Action
     * @throws \Exception
     */
    public function handle(RequestPacket $requestpacket)
    {
        $this->controller->setMethod($this->getMethod());

        $result = [];

        if ($this->action === Method::ACTION_PUSH || $this->action === Method::ACTION_DELETE) {
            if (!is_array($requestpacket->getParams())) {
                throw new \Exception('data is not an array');
            }

            $results = [];

            foreach ($requestpacket->getParams() as $param) {
                $result = $this->controller->{$this->action}($param);
                $results[] = $result->getResult();
            }

            return (new Action())->setHandled(true)
                ->setResult($results)
                ->setError($result->getError());

        } else {
            return $this->controller->{$this->action}($requestpacket->getParams());
        }
    }

    /**
     * @return SessionHelper
     */
    public static function getSessionHelper(): SessionHelper
    {
        $session = self::$sessionHelper;
        if (self::$sessionHelper === null) {
            $session = new SessionHelper(self::SESSION_NAMESPACE);
        }
        return $session;
    }
}
