<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Status extends Module
{
    public static $name = '<span class="glyphicon glyphicon-random"></span> Status Zuordnung';

    private $modifiedStats = null;

    private $jtlStats = array(
        'new' => 'Neu',
        'cancelled' => 'Abgebrochen',
        'paid' => 'Bezahlt',
        'shipped' => 'Versendet',
        'completed' => 'Abgeschlossen'
    );

    public function __construct($db, $config, $shopConfig)
    {
        parent::__construct($db, $config, $shopConfig);

        $customerOrderModel = new \ReflectionClass('\jtl\Connector\Model\CustomerOrder');

        $this->modifiedStats = $this->db->query('SELECT * FROM orders_status WHERE (orders_status_id, language_id) IN (SELECT orders_status_id, MAX(language_id) FROM orders_status GROUP BY orders_status_id)');
    }

    public function form()
    {
        $html = '<div class="form-group">
                        <div class="col-sm-2">
                            <b>Wawi Status</b>
                        </div>
                        <div class="col-sm-3">
                            <b>Modified Status</b>
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
        $this->config->mapping = $_REQUEST['status'];

        return true;
    }
}
