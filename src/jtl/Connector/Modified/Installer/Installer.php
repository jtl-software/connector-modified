<?php
namespace jtl\Connector\Modified\Installer;

use \jtl\Core\Config\Config;
use \jtl\Core\Config\Loader\Json as ConfigJson;
use \jtl\Connector\Modified\Config\Loader\Config as ConfigLoader;
use \jtl\Core\Database\Mysql;
use \jtl\Core\Utilities\Singleton;
use \jtl\Connector\Session\SessionHelper;

class Installer extends Singleton {
    public function __construct() {
        //error_reporting(E_ALL ^ E_NOTICE);
        //ini_set('display_errors',1);
        
        define("APP_DIR", realpath(__DIR__.'/../../../../'));
        
        $session = new SessionHelper("connector_installer");
        
        if(!is_writable(realpath(__DIR__.'/../'))) echo '<div class="alert alert-danger"><b>Cannot save settings.</b> The folder '.realpath(__DIR__.'/../').' is not writable.</div>';
        else {
            $json = new ConfigJson(CONNECTOR_DIR . '../../../config/config.json');
            $config = new Config(array($json));
            $config->addLoader(new ConfigLoader($config->read('connector_root') . '/includes/configure.php'));
            
            $dbconfig = $config->read("db");
            
            $db = Mysql::getInstance();
            
            if (!$db->isConnected()) {
                $db->connect(array(
                    "host" => $dbconfig["host"],
                    "user" => $dbconfig["user"],
                    "password" => $dbconfig["pass"],
                    "name" => $dbconfig["name"]
                ));
            }
            
            $db->setNames();
            
            $sqlite = new \PDO('sqlite:'.realpath(__DIR__.'/../').'/connector.sdb');
            
            // read XTC in-shop configuration from db
            $shopConfig = $db->query("SElECT configuration_key,configuration_value FROM configuration");
            
            
            foreach($shopConfig as $entry) {
                $configArray[$entry['configuration_key']] = $entry['configuration_value'] == 'true' ? 1 : ($entry['configuration_value'] == 'false' ? 0 : $entry['configuration_value']);
            }            
            
            $session->shopConfig = $configArray;
            $session->config = $config;            
            
            $modules = array();
            
            if ($handle = opendir(__DIR__.'/Modules')) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        require(__DIR__.'/Modules/'.$file);
                        $parts = explode('_',$file);
                        $className = current(explode(".",$parts[1]));
                        $fullName = '\\jtl\\Connector\\Modified\\Installer\\Modules\\'.$className;
                        $modules[$className] = new $fullName($db,$sqlite);                                        
                    }
                }
                closedir($handle);
            }        
            
            $firstActive = key($modules);
            
            echo '<form class="form-horizontal" role="form" method="post"><ul class="nav nav-tabs">';
            
            foreach($modules as $class => $instance) {
                $active = $firstActive == $class ? 'active' : '';
                echo '<li class="'.$active.'"><a href="#'.$class.'" data-toggle="tab">'.$instance::$name.'</a></li>';
            }
            
            echo '</ul><br/>';
            
            echo '<div class="tab-content">';
            
            foreach($modules as $class => $instance) {
                $active = $firstActive == $class ? ' active' : '';
                echo '<div class="tab-pane'.$active.'" id="'.$class.'">';
                if(isset($_REQUEST['save'])) $instance->save();
                echo $instance->form();
                echo '</div>';
            }
            
            echo '</div><div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                  <button type="submit" name="save" class="btn btn-primary">Save</button>
                </div>
              </div></form>';
        }                
    }
}