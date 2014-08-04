<?php
namespace jtl\Connector\Modified\Installer\Modules;

use \jtl\Connector\Modified\Installer\Module;

class TaxRate extends Module {
    public static $name = "Default tax rate";
    
    public function form() {
        $this->sqlite->exec("CREATE TABLE IF NOT EXISTS options (
            key TEXT,
            value TEXT)");
        
        $rates = $this->db->query('SELECT r.tax_rates_id,r.tax_rate,r.tax_class_id,r.tax_description,c.tax_class_title FROM tax_rates r LEFT JOIN tax_class c ON r.tax_class_id=c.tax_class_id');
        $current = $this->sqlite->query('SELECT value FROM options WHERE key="tax_rate"');
        $current = $current->fetchColumn();
        
        foreach($rates as $taxRate) {
            $selected = $taxRate['tax_rates_id'] == $current ? ' selected="selected"' : '';
            $options .= '<option value="'.$taxRate['tax_rates_id'].'"'.$selected.'>'.$taxRate['tax_description'].' - '.$taxRate['tax_class_title'].'</option>';
        }
    
        $data['tax'] .= '<div class="form-group">
            <label for="tax_rate" class="col-sm-2 control-label">Default tax rate</label>
                <div class="col-sm-4">
                    <select class="form-control" name="tax_rate" id="tax_rate">'.$options.'</select>
                </div>
        </div>';
        
        return $data['tax'];
    }
    
    public function save() {
        $query = 'INSERT INTO options (key, value) VALUES ("tax_rate","'.$_REQUEST['tax_rate'].'")';
        
        $this->sqlite->query('DELETE FROM options WHERE key="tax_rate"');
        $this->sqlite->query($query);        
        
        echo '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Default tax rate was successfully saved.</div>';
    }
}