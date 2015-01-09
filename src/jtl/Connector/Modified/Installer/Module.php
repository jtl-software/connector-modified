<?php
namespace jtl\Connector\Modified\Installer;

abstract class Module {
	private $_db;
	private $_config;

	public static $name = null;

	public function __construct($db,$config) {
		
	}

	public abstract function form();

	public abstract function save();
}