<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Status extends Module
{
    public static $name = '<span class="glyphicon glyphicon-random"></span> Status Zuordnung';

    private $modifiedStats = null;
    private $defaultLanguage = null;

    private $jtlStats = array(
        'pending' => 'In Bearbeitung',
        'paid' => 'Bezahlt',
        'shipped' => 'Versendet',
        'completed' => 'Bezahlt &amp; Versendet',
        'canceled' => 'Storniert'
    );

    public function __construct($db, $config, $shopConfig)
    {
        parent::__construct($db, $config, $shopConfig);

        $customerOrderModel = new \ReflectionClass('\jtl\Connector\Model\CustomerOrder');

        $defaultLanguage = $this->db->query('SELECT l.languages_id
            FROM languages l
            LEFT JOIN configuration c ON c.configuration_value = l.code
            WHERE c.configuration_key =  "DEFAULT_LANGUAGE"');
        
        if (count($defaultLanguage) > 0) {
            $this->defaultLanguage = $defaultLanguage[0]['languages_id'];
        }

        $this->modifiedStats = $this->db->query('SELECT * FROM orders_status WHERE language_id='.$this->defaultLanguage);
    }

    public function form()
    {
        $default = $this->db->query('SELECT o.orders_status_name
            FROM configuration c
            LEFT JOIN orders_status o ON c.configuration_value = o.orders_status_id
            WHERE c.configuration_key =  "DEFAULT_ORDERS_STATUS_ID" && o.language_id ='.$this->defaultLanguage);
        
        $default = count($default) > 0 ? $default[0]['orders_status_name'] : '';

        $html = '<div class="alert alert-info">Für jeden Auftrags-Zustand aus der Wawi muss hier der zugehörige Shop-Zustand konfiguriert werden. <b>Bitte beachten Sie dass jeder Zustand eindeutig sein muss.</b></div>';
        $html .= '<a class="btn btn-default btn-sm btn-block" href="'.$this->shopConfig['shop']['fullUrl'].'admin/orders_status.php">Shop-Status anlegen und verwalten</a>';
        $html .= '<div class="form-group">
                    <label class="col-sm-2 control-label">Neu</label>
                        <div class="col-sm-3">
                            <p class="form-control-static">'.$default.' (Standard-Status Ihres Shops)</p>
                        </div>
                </div>';

        foreach ($this->jtlStats as $key => $value) {
            $mapping = (array) $this->config->mapping;

            $stats = '';
            
            foreach ($this->modifiedStats as $modified) {
                $selected = ($mapping[$key] == $modified['orders_status_id']) ? ' selected="selected"' : '';
                $stats .= '<option value="'.$modified['orders_status_id'].'"'.$selected.'>'.$modified['orders_status_name'].'</option>';
            }

            $html .= '<div class="form-group">
                    <label class="col-sm-2 control-label">'.$value.'</label>
                        <div class="col-sm-3">
                            <select class="form-control" name="status['.$key.']">'.$stats.'</select>
                        </div>
                </div>';
        }

        return $html;
    }

    public function save()
    {
        if (count(array_unique($_REQUEST['status'])) < count($_REQUEST['status'])) {
            return 'Bitte legen Sie für jeden Status eine eindeutige Shop-Zuweisung fest. Wenn ihr Shop derzeit nicht über genügend Status verfügt, legen Sie bitte die notwendigen zusätzlich an.';
        } else {
            $this->config->mapping = $_REQUEST['status'];

            return true; 
        }
    }
}
