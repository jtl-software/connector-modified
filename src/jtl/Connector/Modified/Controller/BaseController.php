<?php
namespace jtl\Connector\Modified\Controller;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Result\Action;
use jtl\Connector\Core\Rpc\Error;
use jtl\Connector\Model\Statistic;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Formatter\ExceptionFormatter;
use Jtl\Connector\XtcComponents\AbstractBaseController;
use Jtl\Connector\XtcComponents\AbstractBaseMapper;

class BaseController extends AbstractBaseController
{
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

            $mapper = new $class(Mysql::getInstance(), $this->shopConfig, $this->connectorConfig);

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

            /** @var AbstractBaseMapper $mapper */
            $mapper = new $class(Mysql::getInstance(), $this->shopConfig, $this->connectorConfig);

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
                $mapper = new $class(Mysql::getInstance(), $this->shopConfig, $this->connectorConfig);

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
        $action = new Action();

        $action->setHandled(true);

        try {
            $reflect = new \ReflectionClass($this);
            $class = "\\jtl\\Connector\\Modified\\Mapper\\{$reflect->getShortName()}";

            if (!class_exists($class)) {
                throw new \Exception("Class ".$class." not available");
            }

            $mapper = new $class(Mysql::getInstance(), $this->shopConfig, $this->connectorConfig);

            $result = $mapper->delete($model);

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
}
