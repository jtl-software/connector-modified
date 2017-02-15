<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Check extends Module
{
    public static $name = '<span class="glyphicon glyphicon-check"></span> System Check';

    private $hasPassed = true;
    private $checkResults = null;

    private static $checks = array(
        'phpVersion' => array(
            'title' => 'PHP Version',
            'info' => 'PHP 5.4 oder neuer ist für den JTL Connector notwendig.',
            'ok' => 'Ihre version ist: %s',
            'fault' => 'Ihre Version ist: %s',
        ),
        'gdlib' => array(
            'title' => 'GDLib',
            'info' => 'Die PHP GDLib Extension wird benötigt um Thumbnails zu generieren.',
            'ok' => 'GDLib Extension ist verfügbar',
            'fault' => 'GDLib extension ist nicht verfügbar',
        ),
        'sqlite' => array(
            'title' => 'SQLite',
            'info' => 'Die PHP SQLite Extension wird für Session-Daten des Connectors benötigt.',
            'ok' => 'SQLite Extension ist verfügbar',
            'fault' => 'SQLite extension ist nicht verfügbar',
        ),
        'configFile' => array(
            'title' => 'Connector Config Datei',
            'info' => 'Das config Verzeichnis oder die datei "%s" müssen beschreibar sein.',
            'ok' => 'Config beschreibbar',
            'fault' => 'Config nicht beschreibbar',
        ),
        'dbFile' => array(
            'title' => 'Connector SQLite Session Datenbank',
            'info' => 'Die Datenbank-Datei "%s" muss beschreibbar sein.',
            'ok' => 'Datenbank ist beschreibbar',
            'fault' => 'Datenbank ist nicht beschreibbar',
        ),
        'connectorLog' => array(
            'title' => 'Connector Log-Verzeichnis',
            'info' => 'Das Log-Verzeichnis "%s" muss beschreibbar sein.',
            'ok' => 'Log-Verzeichnis ist beschreibbar',
            'fault' => 'Log-Verzeichnis nicht beschreibbar',
        ),
        'connectorTable' => array(
            'title' => 'Mapping-Tabelle',
            'info' => 'Die Mapping-Tabelle muss in der Shop-Datenbank verfügbar sein.',
            'ok' => 'Tabelle wurde erstellt',
            'fault' => 'Fehler beim erstellen',
        ),
        'checksumTable' => array(
            'title' => 'Checksum-Tabelle',
            'info' => 'Die Checksum-Tabelle muss in der Shop-Datenbank verfügbar sein.',
            'ok' => 'Tabelle wurde erstellt',
            'fault' => 'Fehler beim erstellen',
        ),
        'paymentTable' => array(
            'title' => 'Zahlungs-Tabelle',
            'info' => 'Die Zahlungs-Tabelle muss in der Shop-Datenbank verfügbar sein.',
            'ok' => 'Tabelle wurde erstellt',
            'fault' => 'Fehler beim erstellen',
        ),
        'additionalImages' => array(
            'title' => 'Zusätzliche Produkt-Bilder',
            'info' => 'Um diese Funktion zu nutzen müssen zusätzliche Produkt-Bilder in der <a href="%sadmin/configuration.php?gID=4">modified Konfiguration</a> eingestellt werden.',
            'ok' => '%s zusätzliche Bilder',
            'fault' => 'Zusätzliche Bilder deaktiviert',
        ),
        'groups' => array(
            'title' => 'Kundengruppen-Sichtbarkeiten',
            'info' => 'Das Zusatz-Modul "Kundengruppencheck" muss in der <a href="%sadmin/configuration.php?gID=17">modified Konfiguration</a> eingestellt sein.',
            'ok' => 'Modul aktiviert',
            'fault' => 'Modul deaktiviert',
        )
    );

    public function __construct($db, $config, $shopConfig)
    {
        parent::__construct($db, $config, $shopConfig);
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
        return array((version_compare(PHP_VERSION, '5.4') >= 0),array(PHP_VERSION));
    }

    private function gdlib()
    {
        return array((extension_loaded('gd') && function_exists('gd_info')));
    }

    private function sqlite()
    {
        return array((extension_loaded('sqlite3')));
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
        $types = array(
            1 => 'category',
            2 => 'customer',
            4 => 'customer_order',
            8 => 'delivery_note',
            16 => 'image',
            32 => 'manufacturer',
            64 => 'product',
            512 => 'payment',
            1024 => 'crossselling',
            2048 => 'crossselling_group'
        );

        $queryInt = 'CREATE TABLE IF NOT EXISTS %s (
          endpoint_id INT(20) NOT NULL,
          host_id INT(20) NOT NULL,
          PRIMARY KEY (endpoint_id),
          INDEX (host_id)          
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $queryChar = 'CREATE TABLE IF NOT EXISTS %s (
          endpoint_id varchar(20) NOT NULL,
          host_id INT(20) NOT NULL,
          PRIMARY KEY (endpoint_id),
          INDEX (host_id)          
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        foreach($types as $id => $name) {
            if ($id == 16 || $id == 64) {
                $this->db->query(sprintf($queryChar, 'jtl_connector_link_'.$name));
            } else {
                $this->db->query(sprintf($queryInt, 'jtl_connector_link_'.$name));
            }
        }

        return array(true);
    }

    private function checksumTable()
    {
        if (count($this->db->query("SHOW TABLES LIKE 'jtl_connector_product_checksum'")) == 0) {
            $sql = "
                CREATE TABLE IF NOT EXISTS jtl_connector_product_checksum (
                    endpoint_id int(20) unsigned NOT NULL,
                    type tinyint unsigned NOT NULL,
                    checksum varchar(255) NOT NULL,
                    PRIMARY KEY (endpoint_id)
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

    private function paymentTable()
    {
        if (count($this->db->query("SHOW TABLES LIKE 'jtl_connector_payment'")) == 0) {
            $sql = "
                CREATE TABLE IF NOT EXISTS jtl_connector_payment (
                    id int(11) unsigned NOT NULL,
                    customerOrderId int(11) NOT NULL,
                    billingInfo varchar(255) NULL,
                    creationDate datetime NOT NULL,
                    totalSum double NOT NULL,
                    transactionId varchar(255) NULL,
                    paymentModuleCode varchar(64) NULL,
                    PRIMARY KEY (id)
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

    private function additionalImages()
    {
        $additionalImages = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key="MO_PICS"');

        static::$checks['additionalImages']['info'] = sprintf(static::$checks['additionalImages']['info'], $this->shopConfig['shop']['fullUrl']);

        return array(intval($additionalImages[0]['configuration_value']) > 0, $additionalImages[0]['configuration_value']);
    }

    private function groups()
    {
        $groups = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key="GROUP_CHECK"');

        static::$checks['groups']['info'] = sprintf(static::$checks['groups']['info'], $this->shopConfig['shop']['fullUrl']);

        return array($groups[0]['configuration_value'] == 'true');
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
