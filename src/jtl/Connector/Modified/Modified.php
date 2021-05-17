<?php
namespace jtl\Connector\Modified;

use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Event\Product\ProductAfterPushEvent;
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

        if(isset($session->connectorConfig->utf8) && $session->connectorConfig->utf8 !== '0') {
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
        
        if (defined('DIR_ADMIN')){
            require_once(CONNECTOR_DIR.'/../' . DIR_ADMIN . '/includes/version.php');
        } else {
            require_once(CONNECTOR_DIR.'/../admin/includes/version.php');
        }

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
                'pass' => DB_SERVER_PASSWORD,
                'version' => ltrim(DB_VERSION, 'MOD_')
            ),
            'img' => array(
                'original' => DIR_WS_ORIGINAL_IMAGES,
                'thumbnails' => DIR_WS_THUMBNAIL_IMAGES,
                'info' => DIR_WS_INFO_IMAGES,
                'popup' => DIR_WS_POPUP_IMAGES
            )
        );
    }

    /**
     * @param Mysql $db
     * @return array[]
     */
    private function readConfigDb(Mysql $db): array
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

    /**
     * @param RequestPacket $requestpacket
     * @return Action
     * @throws \Exception
     */
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
