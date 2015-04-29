<?php
namespace jtl\Connector\Modified\Controller;

use jtl\Connector\Result\Action;
use jtl\Connector\Model\Statistic;
use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\ConnectorIdentification;
use jtl\Connector\Session\SessionHelper;

class Connector extends Controller
{
    public function statistic(QueryFilter $filter)
    {
        $action = new Action();
        $action->setHandled(true);

        $return = [];

        $mainControllers = array(
            'Category',
            'Customer',
            'CustomerOrder',
            'Image',
            'Product',
            'Manufacturer',
        );

        foreach ($mainControllers as $controller) {
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$controller}";

            if (class_exists($class)) {
                try {
                    $mapper = new $class();

                    $statModel = new Statistic();

                    $statModel->setAvailable($mapper->statistic());
                    $statModel->setControllerName(lcfirst($controller));

                    $return[] = $statModel;
                } catch (\Exception $exc) {
                    $err = new Error();
                    $err->setCode($exc->getCode());
                    $err->setMessage($exc->getMessage());
                    $action->setError($err);
                }
            }
        }

        $action->setResult($return);

        return $action;
    }

    public function pull(QueryFilter $queryfilter)
    {
    }

    public function push(DataModel $model)
    {
    }

    public function delete(DataModel $model)
    {
    }

    public function identify()
    {
        $action = new Action();
        $action->setHandled(true);

        $session = new SessionHelper("modified");
        $config = $session->connectorConfig;

        define('_VALID_XTC', true);
        include $config->connector_root.'/admin/includes/version.php';

        $connector = new ConnectorIdentification();
        $connector->setEndpointVersion('1.0.0');
        $connector->setPlatformName('Modified Shop');
        $connector->setPlatformVersion(PROJECT_VERSION);

        $action->setResult($connector);

        return $action;
    }
}
