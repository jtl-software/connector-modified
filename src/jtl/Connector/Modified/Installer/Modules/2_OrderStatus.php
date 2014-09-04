<?php
namespace jtl\Connector\Modified\Installer\Modules;

use \jtl\Connector\Modified\Installer\Module;

class OrderStatus extends Module {
    public static $name = 'Order status mapping';
    
    private $jtlStats = null;
    private $modifiedStats = null;
    
    public function __construct($db,$sqlite) {
        parent::__construct($db,$sqlite);
        
        $customerOrderModel = new \ReflectionClass('\jtl\Connector\Model\CustomerOrder');
        $this->jtlStats =  $customerOrderModel->getConstants();
        
        $this->modifiedStats = $this->db->query('SELECT * FROM orders_status WHERE (orders_status_id, language_id) IN (SELECT orders_status_id, MAX(language_id) FROM orders_status GROUP BY orders_status_id)');
    }
    
    public function form() {
        $currentMapping = [];
        $mapping = $this->sqlite->query('SELECT * FROM status');
        
        foreach($mapping as $map) {
            $currentMapping[$map['jtl']] = $map['modified'];
        }
        
        foreach($this->jtlStats as $key => $value) {
            if(!in_array($value,array('insert','update','delete','complete')) && strpos($key,'PAYMENT_STATUS') === false) {
            $options = '';
            foreach($this->modifiedStats as $modifiedStat) {
                $selected = ($modifiedStat['orders_status_id'] == $currentMapping[$value]) ? ' selected="selected"' : '';
                $options .= '<option value="'.$modifiedStat['orders_status_id'].'"'.$selected.'>'.$modifiedStat['orders_status_name'].'</option>';
            }
        
            $data['stats'] .= '<div class="form-group">
                <label for="'.$value.'" class="col-sm-2 control-label">'.ucfirst(str_replace('_',' ',$value)).'</label>
                    <div class="col-sm-4">
                        <select class="form-control" name="'.$value.'" id="'.$value.'">'.$options.'</select>
                    </div>
            </div>';
            }
        }
 
        return $data['stats']; 
    }
    
    public function save() {
        $this->sqlite->exec("CREATE TABLE IF NOT EXISTS status (
                        jtl TEXT,
                        modified INTEGER)");
        
        $query = 'INSERT INTO status (jtl, modified) VALUES ';
        
        foreach($this->jtlStats as $key => $value) {
            if(isset($_REQUEST[$value])) $query .= '("'.$value.'",'.$_REQUEST[$value].'),';
        }
        
        $this->sqlite->query('DELETE FROM status');
        $this->sqlite->query(trim($query,','));
        
        echo '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Mapping was successfully saved.</div>';        
    }
}