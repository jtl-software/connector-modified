<?php
namespace jtl\Connector\Modified\Installer;

class Module {
    public $db = null;
    public $sqlite = null;
    
    public function __construct($db,$sqlite) {
        $this->db = $db;
        $this->sqlite = $sqlite;        
    }
    
    public function form() {
        return null;
    }
    
    public function save() {        
    }
}