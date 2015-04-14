<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Status extends Module
{
    public static $name = '<span class="glyphicon glyphicon-random"></span> Status mapping';

    private $jtlOrderStats = null;
    private $jtlPaymentStats = null;
    private $modifiedStats = null;

    public function __construct($db, $config, $shopConfig)
    {
        parent::__construct($db, $config, $shopConfig);

        $customerOrderModel = new \ReflectionClass('\jtl\Connector\Model\CustomerOrder');

        foreach ($customerOrderModel->getConstants() as $key => $value) {
            if (strpos($key, 'PAYMENT') !== false) {
                $this->jtlPaymentStats[$key] = $value;
            } else {
                $this->jtlOrderStats[$key] = $value;
            }
        }

        $this->modifiedStats = $this->db->query('SELECT * FROM orders_status WHERE (orders_status_id, language_id) IN (SELECT orders_status_id, MAX(language_id) FROM orders_status GROUP BY orders_status_id)');
    }

    public function form()
    {
        $html = '<div class="form-group">
                        <div class="col-sm-2">
                            <b>Modified shop status</b>
                        </div>
                        <div class="col-sm-3">
                            <b>Wawi Order status</b>
                        </div>
                        <div class="col-sm-3">
                            <b>Wawi Payment status</b>
                        </div>
                </div>';

        foreach ($this->modifiedStats as $status) {
            $id = 'status_'.$status['orders_status_id'];

            $mapping = (array) $this->config->mapping;

            $currentValues = explode('|', $mapping[$id]);

            $orderStats = '';
            $paymentStats = '';

            foreach ($this->jtlOrderStats as $key => $value) {
                $selected = ($currentValues[0] == $value) ? ' selected="selected"' : '';
                $orderStats .= '<option value="'.$value.'"'.$selected.'>'.ucfirst(str_replace('_', ' ', $value)).'</option>';
            }

            foreach ($this->jtlPaymentStats as $key => $value) {
                $selected = ($currentValues[1] == $value) ? ' selected="selected"' : '';
                $paymentStats .= '<option value="'.$value.'"'.$selected.'>'.ucfirst(str_replace('_', ' ', $value)).'</option>';
            }

            $html .= '<div class="form-group">
                    <label class="col-sm-2 control-label">'.$status['orders_status_name'].'</label>
                        <div class="col-sm-3">
                            <select class="form-control" name="status['.$id.'][order]" id="order_'.$id.'">'.$orderStats.'</select>
                        </div>
                        <div class="col-sm-3">
                            <select class="form-control" name="status['.$id.'][payment]" id="payment_'.$id.'">'.$paymentStats.'</select>
                        </div>
                </div>';
        }

        return $html;
    }

    public function save()
    {
        $mapping = array();

        foreach ($_REQUEST['status'] as $modified => $host) {
            $mapping[$modified] = implode($host, '|');
        }

        $this->config->mapping = $mapping;

        return true;
    }
}
