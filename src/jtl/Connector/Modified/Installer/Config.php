<?php
namespace jtl\Connector\Modified\Installer;

class Config
{
    private $data;

    public function __construct($file)
    {
        $this->data = json_decode(@file_get_contents($file));
        if (is_null($this->data)) {
            $this->data = new \stdClass();
        }
    }

    public function __set($name, $value)
    {
        $this->data->$name = $value;
    }

    public function __get($name)
    {
        return $this->data->$name;
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
