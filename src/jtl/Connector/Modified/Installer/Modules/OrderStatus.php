<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class OrderStatus extends Module
{
    public static $name = '<span class="glyphicon glyphicon-random"></span> Order status mapping';

    private $jtlStats = null;
    private $modifiedStats = null;

    public function __construct($db, $config)
    {
        parent::__construct($db, $config);

        $customerOrderModel = new \ReflectionClass('\jtl\Connector\Model\CustomerOrder');
        $this->jtlStats =  $customerOrderModel->getConstants();

        $this->modifiedStats = $this->db->query('SELECT * FROM orders_status WHERE (orders_status_id, language_id) IN (SELECT orders_status_id, MAX(language_id) FROM orders_status GROUP BY orders_status_id)');
    }

    public function form()
    {
        $currentMapping = [];

        if (!is_null($this->config->mapping)) {
            foreach ($this->config->mapping as $key => $value) {
                $currentMapping[$key] = $value;
            }
        }

        foreach ($this->jtlStats as $key => $value) {
            if (!in_array($value, array('insert', 'update', 'delete', 'complete')) && strpos($key, 'PAYMENT_STATUS') === false) {
                $options = '';

                foreach ($this->modifiedStats as $modifiedStat) {
                    $selected = ($modifiedStat['orders_status_id'] == $currentMapping[$value]) ? ' selected="selected"' : '';
                    $options .= '<option value="'.$modifiedStat['orders_status_id'].'"'.$selected.'>'.$modifiedStat['orders_status_name'].'</option>';
                }

                $data['stats'] .= '<div class="form-group">
                    <label for="'.$value.'" class="col-sm-2 control-label">'.ucfirst(str_replace('_', ' ', $value)).'</label>
                        <div class="col-sm-4">
                            <select class="form-control" name="'.$value.'" id="'.$value.'">'.$options.'</select>
                        </div>
                </div>';
            }
        }

        return $data['stats'];
    }

    public function save()
    {
        $mapping = array();

        foreach ($this->jtlStats as $key => $value) {
            if (isset($_REQUEST[$value])) {
                $mapping[$value] = $_REQUEST[$value];
            }
        }

        $this->config->mapping = $mapping;

        return true;
    }
}
