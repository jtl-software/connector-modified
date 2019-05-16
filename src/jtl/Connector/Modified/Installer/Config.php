<?php
namespace jtl\Connector\Modified\Installer;

use Noodlehaus\Exception\FileNotFoundException;

class Config
{
    private $data;

    public function __construct($file)
    {
        try{
            $this->data = \Noodlehaus\Config::load($file)->all();
        } catch (FileNotFoundException $e) {
            $this->data = [];
            $this->data['use_varCombi_logic'] = true;
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function save()
    {
        if (file_put_contents(CONNECTOR_DIR.'/config/config.json', json_encode($this->data)) === false) {
            return false;
        } else {
            return true;
        }
    }
}
