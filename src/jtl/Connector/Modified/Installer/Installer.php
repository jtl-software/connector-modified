<?php
namespace jtl\Connector\Modified\Installer;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Modified\Installer\Config;

class Installer
{
    private $modules = array(
        'check' => 'Check',
        'connector' => 'Connector',
        'status' => 'Status',
        'thumbs' => 'ThumbMode',
        'tax_rate' => 'TaxRate',
        'dev_logging' => 'DevLogging'
    );

    private $connectorConfig = null;

    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 1);
        session_start();
        
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

        $moduleInstances = [];
        $moduleErrors = [];

        foreach ($this->modules as $id => $module) {
            $className = '\\jtl\\Connector\\Modified\\Installer\\Modules\\'.$module;
            $moduleInstances[$id] = new $className($db, $this->connectorConfig, $shopConfig);
        }
    
        if (isset($_REQUEST['save'])) {
            foreach ($moduleInstances as $class => $instance) {
                $moduleSave = $instance->save();
                if ($moduleSave !== true) {
                    $moduleErrors[] = $moduleSave;
                }
            }
        }
        
        if (isset($_REQUEST['save'])) {
            if (count($moduleErrors) == 0) {
                if (!$this->connectorConfig->save()) {
                    $_SESSION['error'] = true;
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    exit();
                } else {
                    $_SESSION['success'] = true;
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    exit();
                }
            } else {
                $html = '<div class="alert alert-danger">Folgende Fehler traten auf:
		        		<br>
		        		<ul>';
            
                foreach ($moduleErrors as $error) {
                    $html .= '<li>'.$error.'</li>';
                }
            
                $html .= '</ul>
		        		</div>';
                
                $_SESSION['fail'] = $html;
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit();
            }
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

            foreach ($moduleInstances as $class => $instance) {
                $active = $class == 'check' ? ' active' : '';

                echo '<div class="tab-pane'.$active.'" id="'.$class.'">';
                echo $instance->form();
                echo '</div>';
            }
            
            if (isset($_SESSION['error']))
            {
                echo '<div class="alert alert-danger">Fehler beim Schreiben der config.json Datei.</div>';
                unset($_SESSION['error']);
            } elseif (isset($_SESSION['success']))
            {
                echo '<div class="alert alert-success">Connector Konfiguration wurde gespeichert.</div>';
                echo '<div class="alert alert-danger"><b>ACHTUNG:</b><br/>
                            Bitte sorgen Sie nach erfolgreicher Installation des Connectors unbedingt dafür, dass dieser Installer
                            sowie die Datei config.json im Verzeichnis config nicht öffentlich les- und ausführbar sind!</div>';
                unset($_SESSION['success']);
            }elseif (isset($_SESSION['fail']))
            {
                echo $_SESSION['fail'];
                unset($_SESSION['fail']);
            }
            
            echo '</div>';

            echo '<button type="submit" name="save" class="btn btn-primary btn-block"><span class="glyphicon glyphicon-save"></span> Connector Konfiguration speichern</button>';
        } else {
            echo '<div class="alert alert-danger">Bitte beheben Sie die angezeigten Fehler bevor Sie mit der Konfiguration fortfahren können.</div>';
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
