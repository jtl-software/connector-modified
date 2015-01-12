<?php
namespace jtl\Connector\Modified\Installer;

abstract class Module {
	protected $db;
	protected $config;

	public static $name = null;

	public function __construct($db,$config) {
		$this->db = $db;
		$this->config = $config;
	}

	public abstract function form();

	public abstract function save();
}