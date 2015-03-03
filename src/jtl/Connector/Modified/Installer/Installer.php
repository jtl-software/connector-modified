<?php
namespace jtl\Connector\Modified\Installer;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Modified\Installer\Config;

class Installer
{
    private $_modules = array(
        'check' => 'Check',
        'connector' => 'Connector',
        //'levels' => 'CategoryLevels',
        'tax_rate' => 'TaxRate',
        'order_status' => 'OrderStatus'
    );

    private $connectorConfig = null;

    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 1);

        $shopConfig = $this->readConfigFile();
        $this->connectorConfig = new Config(CONNECTOR_DIR.'/config/config.json');

        $db = Mysql::getInstance();

        if (!$db->isConnected()) {
            $db->connect(array(
                "host" => $shopConfig['db']["host"],
                "user" => $shopConfig['db']["user"],
                "password" => $shopConfig['db']["pass"],
                "name" => $shopConfig['db']["name"],
            ));
        }

        $db->setNames();

        $moduleInstances = array();

        foreach ($this->_modules as $id => $module) {
            $className = '\\jtl\\Connector\\Modified\\Installer\\Modules\\'.$module;
            $moduleInstances[$id] = new $className($db, $this->connectorConfig);
        }

        if ($moduleInstances['check']->hasPassed()) {
            echo '<ul class="nav nav-tabs">';

            foreach ($moduleInstances as $class => $instance) {
                $active = $class == 'check' ? 'active' : '';
                echo '<li class="'.$active.'"><a href="#'.$class.'" data-toggle="tab"><b>'.$instance::$name.'</b></a></li>';
            }

            echo '</ul>
	        	<br>
	        	<div class="tab-content">';

            $moduleErrors = array();

            foreach ($moduleInstances as $class => $instance) {
                $active = $class == 'check' ? ' active' : '';

                if (isset($_REQUEST['save'])) {
                    $moduleSave = $instance->save();
                    if ($moduleSave !== true) {
                        $moduleErrors[] = $moduleSave;
                    }
                }

                echo '<div class="tab-pane'.$active.'" id="'.$class.'">';
                echo $instance->form();
                echo '</div>';
            }

            echo '</div>';

            if (isset($_REQUEST['save'])) {
                if (count($moduleErrors) == 0) {
                    if (!$this->connectorConfig->save()) {
                        echo '<div class="alert alert-danger">Error writing the config.json file.</div>';
                    } else {
                        echo '<div class="alert alert-success">Connector configuration was successfully saved.</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">The following errors occured:
		        		<br>
		        		<ul>';

                    foreach ($moduleErrors as $error) {
                        var_dump($moduleErrors);
                        echo '<li>'.$error.'</li>';
                    }

                    echo '</ul>
		        		</div>';
                }
            }

            echo '<button type="submit" name="save" class="btn btn-primary btn-block"><span class="glyphicon glyphicon-save"></span> Save connector configuration</button>';
        } else {
            echo '<div class="alert alert-danger">Please fix the following errors before you can continue to configure the connector.</div>';
            echo $moduleInstances['check']->form();
        }
    }

    private function readConfigFile()
    {
        require_once realpath(CONNECTOR_DIR.'/../').'/includes/configure.php';

        return array(
            'shop' => array(
                'url' => HTTP_SERVER,
                'folder' => DIR_WS_CATALOG,
                'fullUrl' => HTTP_SERVER.DIR_WS_CATALOG,
            ),
            'db' => array(
                'host' => DB_SERVER,
                'name' => DB_DATABASE,
                'user' => DB_SERVER_USERNAME,
                'pass' => DB_SERVER_PASSWORD,
            ),
            'img' => array(
                'original' => DIR_WS_ORIGINAL_IMAGES,
            )
        );
    }
}
