<?php

namespace jtl\Connector\Modified\Installer;

abstract class AbstractModule
{
    protected $db;
    protected $config;
    protected $shopConfig;
    protected $errorMessages = [];

    public static $name = null;

    public function __construct($db, $config, $shopConfig)
    {
        $this->db = $db;
        $this->config = $config;
        $this->shopConfig = $shopConfig;
    }

    abstract public function form(): string;

    abstract public function save(): bool;

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
