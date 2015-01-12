<?php
namespace jtl\Connector\Modified\Installer\Modules;

use \jtl\Connector\Modified\Installer\Module;

class Check extends Module {
	public static $name = '<span class="glyphicon glyphicon-check"></span> System check';

	private $_hasPassed = true;
    private $_checkResults = null;

	private static $checks = array(
        'php_version' => array(
            'title' => 'PHP version',
            'info' => 'PHP 5.3 or higher is recommend to run the JTL connector.',
            'ok' => 'Your version is: %s',
            'fault' => 'Your version is: %s'
        ),
        'gdlib' => array(
            'title' => 'GDLib',
            'info' => 'The PHP GDLib Extension is required to scale images and generate thumbnails.',
            'ok' => 'GDLib Extension is available',
            'fault' => 'GDLib extension is not available'
        ),
        'config_file' => array(
            'title' => 'Connector config file',
            'info' => 'The connector config folder or file "%s" must be writable.',
            'ok' => 'Config is writable',
            'fault' => 'Config is not writable'
        ),
        'connector_log' => array(
            'title' => 'Connector logs folder',
            'info' => 'The folder "%s" must be writable.',
            'ok' => 'Logs folder is writable',
            'fault' => 'Logs folder is not writable'
        )
    );

    public function __construct($db,$config) {
        $this->runChecks();
    }

    public function runChecks() {
        foreach(self::$checks as $check => $data) {
            $this->_checkResults[$check] = $this->$check();
            if(!$this->_checkResults[$check][0]) $this->_hasPassed = false;
        }
    }

    public function form() {
        $html = '<table class="table table-striped"><tbody>';
        foreach(self::$checks as $check => $data) {
            $result = $this->_checkResults[$check];

            $html .= '<tr class="'.($result[0] === true ? '' : 'danger').'"><td><b>'.$data['title'].'</b><br/>'.vsprintf($data['info'],$result[1]).'</td><td><h4 class="pull-right">';
            $html .= $result[0] ? '<span class="label label-success"><span class="glyphicon glyphicon-ok"></span> '.vsprintf($data['ok'],$result[1]).'</span>' : '<span class="label label-danger"><span class="glyphicon glyphicon-warning-sign"></span> '.vsprintf($data['fault'],$result[1]).'</span>';
            $html .= '</h4></td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function php_version() {
        return array((version_compare(PHP_VERSION, '5.3') >= 0),array(PHP_VERSION));
    }

    private function gdlib() {
        return array((extension_loaded('gd') && function_exists('gd_info')));
    }

    private function config_file() {
        $path = CONNECTOR_DIR.'/config';
        if(file_exists($path.'/config.json')) $path = $path.'/config.json';
        return array(is_writable($path),array($path));
    }

    private function connector_log() {
        $path = CONNECTOR_DIR.'/logs';
        return array(is_writable($path),array($path));
    }

    public function save() {
        return true;
    }

    public function hasPassed() {
    	return $this->_hasPassed;
    }
}