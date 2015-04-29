<?php
namespace jtl\Connector\Modified\Installer;

abstract class Module
{
    protected $db;
    protected $config;
    protected $shopConfig;

    public static $name = null;

    public function __construct($db, $config, $shopConfig)
    {
        $this->db = $db;
        $this->config = $config;
        $this->shopConfig = $shopConfig;
    }

    abstract public function form();

    abstract public function save();
}
