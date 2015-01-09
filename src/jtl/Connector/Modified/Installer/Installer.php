<?php
namespace jtl\Connector\Modified\Installer;

use \jtl\Connector\Core\Database\Mysql;
use \jtl\Connector\Session\SessionHelper;

class Installer {
    public function __construct() {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors',1);
    }
}
