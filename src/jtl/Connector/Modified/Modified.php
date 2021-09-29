<?php
namespace jtl\Connector\Modified;

use jtl\Connector\Core\Rpc\Error;
use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Event\Product\ProductAfterPushEvent;
use jtl\Connector\Modified\Util\ShopVersion;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\Image;
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

    protected $controller;

    protected $action;

    public function initialize()
    {
        $session = self::getSessionHelper();
        if (!isset($session->shopConfig)) {
            $session->shopConfig = $this->readConfigFile();
        }

        ShopVersion::setShopVersion($session->shopConfig['shop']['version']);

        if (!isset($session->connectorConfig)) {
            $session->connectorConfig = json_decode(@file_get_contents(CONNECTOR_DIR.'/config/config.json'));
        }

        $db = Mysql::getInstance();

        if (!$db->isConnected()) {
            $db->connect([
                "host" => $session->shopConfig['db']["host"],
                "user" => $session->shopConfig['db']["user"],
                "password" => $session->shopConfig['db']["pass"],
                "name" => $session->shopConfig['db']["name"]
            ]);
        }

        if (isset($session->connectorConfig->utf8) && $session->connectorConfig->utf8 !== '0') {
            $db->setNames();
            $db->setCharset();
        }

        if (!isset($session->shopConfig['settings'])) {
            $session->shopConfig += $this->readConfigDb($db);
        }

        $this->update($db);

        $this->setPrimaryKeyMapper(new PrimaryKeyMapper());
        $this->setTokenLoader(new TokenLoader());
        $this->setChecksumLoader(new ChecksumLoader());
    }

    private function readConfigFile()
    {
        require_once(CONNECTOR_DIR.'/../includes/configure.php');
        require_once(CONNECTOR_DIR.'/../inc/set_admin_directory.inc.php');
        
        if (defined('DIR_ADMIN')) {
            require_once(CONNECTOR_DIR.'/../' . DIR_ADMIN . '/includes/version.php');
        } else {
            require_once(CONNECTOR_DIR.'/../admin/includes/version.php');
        }

        return [
            'shop' => [
                'url' => HTTP_SERVER,
                'folder' => DIR_WS_CATALOG,
                'path' => DIR_FS_DOCUMENT_ROOT,
                'fullUrl' => HTTP_SERVER . DIR_WS_CATALOG,
                'version' => sprintf('%s.%s', PROJECT_MAJOR_VERSION, PROJECT_MINOR_VERSION)
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

            usort($versions, 'version_compare');

            foreach ($versions as $version) {
                if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), $version) == -1) {
                    include(CONNECTOR_DIR.'/db/updates/' . $version . '.php');
                    file_put_contents(CONNECTOR_DIR.'/db/version', $version);
                }
            }
        }
    }

    public function canHandle()
    {
        $controller = RpcMethod::buildController($this->getMethod()->getController());
        $class = "\\jtl\\Connector\\Modified\\Controller\\{$controller}";

        if (class_exists($class)) {
            $this->controller = $class::getInstance();
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
                throw new \Exception('Data is not an array');
            }

            $action = new Action();
            $results = [];

            /** @var DataModel $model */
            foreach ($requestpacket->getParams() as $model) {
                $result = $this->controller->{$this->action}($model);

                if ($result->getError()) {
                    $this->extendErrorMessage($model, $result->getError());
                    throw new \Exception($result->getError()->getMessage());
                }

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

    /**
     * @param DataModel $model
     * @param Error $error
     */
    protected function extendErrorMessage(DataModel $model, Error $error)
    {
        $controllerToIdentityGetter = [
            'ProductPrice' => 'getProductId',
            'ProductStockLevel' => 'getProductId',
            'StatusChange' => 'getCustomerOrderId',
            'DeliveryNote' => 'getCustomerOrderId',
            'Image' => 'getForeignKey',
        ];

        $controllerName = (new \ReflectionClass($this->controller))->getShortName();

        $identityGetter = $controllerToIdentityGetter[$controllerName] ?? 'getId';
        $identity = null;
        if (method_exists($model, $identityGetter)) {
            $identity = $model->{$identityGetter}();
        }

        if ($identity !== null) {
            $messageParts = [$controllerName];

            if ($model instanceof Image) {
                $messageParts[] = sprintf('Related type %s (hostId = %d)', ucfirst($model->getRelationType()), $identity->getHost());
            } else {
                $messageParts[] = sprintf('hostId = %d', $identity->getHost());
            }

            $messageParts[] = $error->getMessage();
            $error->setMessage(implode(' | ', $messageParts));
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
