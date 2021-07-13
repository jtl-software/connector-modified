<?php

namespace jtl\Connector\Modified\Controller;

use jtl\Connector\Core\Database\IDatabase;

class SharedController extends AbstractController
{
    /**
     * @var string
     */
    protected $controllerName;

    /**
     * Controller constructor.
     * @param IDatabase $db
     * @param array $shopConfig
     * @param \stdClass $connectorConfig
     * @param string $controllerName
     * @throws \Exception
     */
    public function __construct(IDatabase $db, array $shopConfig, \stdClass $connectorConfig, string $controllerName)
    {
        $this->controllerName = $controllerName;
        parent::__construct($db, $shopConfig, $connectorConfig);
    }

    /**
     * @return string
     */
    public function getControllerName(): string
    {
        return $this->controllerName;
    }
}
