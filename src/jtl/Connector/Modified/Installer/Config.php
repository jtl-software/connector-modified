<?php
namespace jtl\Connector\Modified\Installer;

class Config {
	private $_data;

	public function __construct($file) {
		$this->_data = json_decode(@file_get_contents($file));
		if(is_null($this->_data)) $this->_data = new \stdClass;
	}

	public function __set($name,$value) {
        $this->_data->$name = $value;
    }

    public function __get($name) {
        return $this->_data->$name;
    }

    public function save() {
    	if(file_put_contents(CONNECTOR_DIR.'/config/config.json',json_encode($this->_data)) === false) return false;
	    else return true;
    }
}