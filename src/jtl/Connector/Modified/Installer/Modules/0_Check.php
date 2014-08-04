<?php
namespace jtl\Connector\Modified\Installer\Modules;

use \jtl\Connector\Modified\Installer\Module;

class Check extends Module {
    public static $name = 'System check';
    
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
        'core_tmp' => array(
            'title' => 'Core temporary folder',
            'info' => 'The folder "%s" must be writable.',
            'ok' => 'Temp folder is writable',
            'fault' => 'Temp folder is not writable'
        ),
        'connector_log' => array(
            'title' => 'Connector logs folder',
            'info' => 'The folder "%s" must be writable.',
            'ok' => 'Logs folder is writable',
            'fault' => 'Logs folder is not writable'
        )
    );
    
    public function form() {
        $html = '<table class="table table-striped"><tbody>';
        foreach(self::$checks as $check => $data) {
            $result = $this->{$check}();
            $html .= '<tr><td><b>'.$data['title'].'</b><br/>'.vsprintf($data['info'],$result[1]).'</td><td><h4 class="pull-right">';
            $html .= $result[0] ? '<span class="label label-success">'.vsprintf($data['ok'],$result[1]).'</span>' : '<span class="label label-danger">'.vsprintf($data['fault'],$result[1]).'</span>';
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
    
    private function core_tmp() {
        $path = realpath(__DIR__.'/../../../../../../').'/vendor/jtl/core/tmp';
        return array(is_writable($path),array($path));
    }
    
    private function connector_log() {
        $path = realpath(__DIR__.'/../../../../../../').'/vendor/jtl/core/logs';
        return array(is_writable($path),array($path));
    }
}