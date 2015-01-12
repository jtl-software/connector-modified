<?php
namespace jtl\Connector\Modified\Installer\Modules;

use \jtl\Connector\Modified\Installer\Module;

class TaxRate extends Module {
    public static $name = '<span class="glyphicon glyphicon-euro"></span> Default tax rate';

    public function form() {
        $rates = $this->db->query('SELECT r.tax_rates_id,r.tax_rate,r.tax_class_id,r.tax_description,c.tax_class_title FROM tax_rates r LEFT JOIN tax_class c ON r.tax_class_id=c.tax_class_id');

        foreach($rates as $taxRate) {
            $selected = $taxRate['tax_rates_id'] == $this->config->tax_rate ? ' selected="selected"' : '';
            $options .= '<option value="'.$taxRate['tax_rates_id'].'"'.$selected.'>'.$taxRate['tax_description'].' - '.$taxRate['tax_class_title'].'</option>';
        }

        $data['tax'] .= '<div class="form-group">
            <label for="tax_rate" class="col-xs-2 control-label">Default tax rate</label>
                <div class="col-xs-6">
                    <select class="form-control" name="tax_rate" id="tax_rate">'.$options.'</select>
                    <span id="helpBlock" class="help-block">
                        Please select the tax rate which should be used by default.
                    </span>
                </div>
        </div>';

        return $data['tax'];
    }

    public function save() {
        $this->config->tax_rate = $_REQUEST['tax_rate'];

        return true;
    }
}