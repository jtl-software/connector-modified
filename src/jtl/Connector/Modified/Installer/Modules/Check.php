<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Check extends Module
{
    public static $name = '<span class="glyphicon glyphicon-check"></span> System check';

    private $hasPassed = true;
    private $checkResults = null;

    private static $checks = array(
        'phpVersion' => array(
            'title' => 'PHP version',
            'info' => 'PHP 5.3 or higher is recommend to run the JTL connector.',
            'ok' => 'Your version is: %s',
            'fault' => 'Your version is: %s',
        ),
        'gdlib' => array(
            'title' => 'GDLib',
            'info' => 'The PHP GDLib Extension is required to scale images and generate thumbnails.',
            'ok' => 'GDLib Extension is available',
            'fault' => 'GDLib extension is not available',
        ),
        'configFile' => array(
            'title' => 'Connector config file',
            'info' => 'The config folder or file "%s" must be writable.',
            'ok' => 'Config is writable',
            'fault' => 'Config is not writable',
        ),
        'dbFile' => array(
            'title' => 'Connector sqlite session database',
            'info' => 'The database file "%s" must be writable.',
            'ok' => 'Database is writable',
            'fault' => 'Database is not writable',
        ),
        'connectorLog' => array(
            'title' => 'Connector logs folder',
            'info' => 'The logs folder "%s" must be writable.',
            'ok' => 'Logs folder is writable',
            'fault' => 'Logs folder is not writable',
        ),
        'connectorTable' => array(
            'title' => 'Connector mapping table',
            'info' => 'The mapping table must be available in the shop database.',
            'ok' => 'Table was created',
            'fault' => 'Failed to create table',
        ),
    );

    public function __construct($db, $config)
    {
        parent::__construct($db, $config);
        $this->runChecks();
    }

    public function runChecks()
    {
        foreach (self::$checks as $check => $data) {
            $this->checkResults[$check] = $this->$check();
            if (!$this->checkResults[$check][0]) {
                $this->hasPassed = false;
            }
        }
    }

    public function form()
    {
        $html = '<table class="table table-striped"><tbody>';
        foreach (self::$checks as $check => $data) {
            $result = $this->checkResults[$check];

            $html .= '<tr class="'.($result[0] === true ? '' : 'danger').'"><td><b>'.$data['title'].'</b><br/>'.vsprintf($data['info'], $result[1]).'</td><td><h4 class="pull-right">';
            $html .= $result[0] ? '<span class="label label-success"><span class="glyphicon glyphicon-ok"></span> '.vsprintf($data['ok'], $result[1]).'</span>' : '<span class="label label-danger"><span class="glyphicon glyphicon-warning-sign"></span> '.vsprintf($data['fault'], $result[1]).'</span>';
            $html .= '</h4></td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function phpVersion()
    {
        return array((version_compare(PHP_VERSION, '5.3') >= 0),array(PHP_VERSION));
    }

    private function gdlib()
    {
        return array((extension_loaded('gd') && function_exists('gd_info')));
    }

    private function configFile()
    {
        $path = CONNECTOR_DIR.'/config';
        if (file_exists($path.'/config.json')) {
            $path = $path.'/config.json';
        }

        return array(is_writable($path), array($path));
    }

    private function dbFile()
    {
        $path = CONNECTOR_DIR.'/db/connector.s3db';

        return array(is_writable($path), array($path));
    }

    private function connectorLog()
    {
        $path = CONNECTOR_DIR.'/logs';

        return array(is_writable($path), array($path));
    }

    private function connectorTable()
    {
        if (count($this->db->query("SHOW TABLES LIKE 'jtl_connector_link'")) == 0) {
            $sql = "
                CREATE TABLE IF NOT EXISTS `jtl_connector_link` (
                  `endpointId` int(10) NOT NULL,
                  `hostId` int(10) NOT NULL,
                  `type` int(10),
                  PRIMARY KEY (`endpointId`,`hostId`,`type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ";

            try {
                $this->db->query($sql);

                return array(true);
            } catch (\Exception $e) {
                return array(false);
            }
        }

        return array(true);
    }

    public function save()
    {
        return true;
    }

    public function hasPassed()
    {
        return $this->hasPassed;
    }
}
