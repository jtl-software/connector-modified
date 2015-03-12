<?php
namespace jtl\Connector\Modified\Controller;

use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Result\Action;
use jtl\Connector\Core\Rpc\Error;
use jtl\Connector\Model\Statistic;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Formatter\ExceptionFormatter;

class BaseController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    public function pull(QueryFilter $queryfilter)
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $reflect = new \ReflectionClass($this);
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";

            if (!class_exists($class)) {
                throw new \Exception("Class ".$class." not available");
            }

            $mapper = new $class();

            $result = $mapper->pull(null, $queryfilter->getLimit());

            $action->setResult($result);
        } catch (\Exception $exc) {
            Logger::write(ExceptionFormatter::format($exc), Logger::WARNING, 'controller');

            $err = new Error();
            $err->setCode($exc->getCode());
            $err->setMessage($exc->getFile().' ('.$exc->getLine().'):'.$exc->getMessage());
            $action->setError($err);
        }

        return $action;
    }

    public function push(DataModel $model)
    {
        $action = new Action();

        $action->setHandled(true);

        try {
            $reflect = new \ReflectionClass($this);
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";

            if (!class_exists($class)) {
                throw new \Exception("Class ".$class." not available");
            }

            $mapper = new $class();

            $result = $mapper->push($model);

            $action->setResult($result);
        } catch (\Exception $exc) {
            Logger::write(ExceptionFormatter::format($exc), Logger::WARNING, 'controller');

            $err = new Error();
            $err->setCode($exc->getCode());
            $err->setMessage($exc->getFile().' ('.$exc->getLine().'):'.$exc->getMessage());
            $action->setError($err);
        }

        return $action;
    }

    public function statistic(QueryFilter $filter)
    {
        $reflect = new \ReflectionClass($this);
        $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";

        if (class_exists($class)) {
            $action = new Action();
            $action->setHandled(true);

            try {
                $mapper = new $class();

                $statModel = new Statistic();

                $statModel->setAvailable($mapper->statistic());
                $statModel->setControllerName(lcfirst($reflect->getShortName()));

                $action->setResult($statModel);
            } catch (\Exception $exc) {
                Logger::write(ExceptionFormatter::format($exc), Logger::WARNING, 'controller');

                $err = new Error();
                $err->setCode($exc->getCode());
                $err->setMessage($exc->getMessage());
                $action->setError($err);
            }

            return $action;
        }
    }

    public function delete(DataModel $model)
    {

    }
}
