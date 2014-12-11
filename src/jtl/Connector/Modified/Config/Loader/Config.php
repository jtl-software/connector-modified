<?php
namespace jtl\Connector\Modified\Config\Loader;

use \jtl\Connector\Core\Config\Loader\Base as BaseLoader;
use \jtl\Connector\Core\Filesystem\Tool;
use \jtl\Connector\Core\Exception\ConfigException;

class Config extends BaseLoader
{

    const DELIMITER = '::';

    //Configuration params in modified-Config
    const CFG_SHOP_PATH = 'DIR_FS_DOCUMENT_ROOT';
    const CFG_SHOP_SERVER = 'HTTP_SERVER';
    const CFG_SHOP_FOLDER = 'DIR_WS_CATALOG';
    const CFG_DB_HOST = 'DB_SERVER';
    const CFG_DB_NAME = 'DB_DATABASE';
    const CFG_DB_USER = 'DB_SERVER_USERNAME';
    const CFG_DB_PASS = 'DB_SERVER_PASSWORD';
    const CFG_IMG_ORIGINAL = 'DIR_WS_ORIGINAL_IMAGES';

    //Constant translations/keys
    protected $trans = array(
      self::CFG_SHOP_PATH => 'shop::path',
      self::CFG_SHOP_SERVER => 'shop::url',
      self::CFG_SHOP_FOLDER => 'shop::folder',
      self::CFG_DB_HOST => 'db::host',
      self::CFG_DB_NAME => 'db::name',
      self::CFG_DB_USER => 'db::user',
      self::CFG_DB_PASS => 'db::pass',
      self::CFG_IMG_ORIGINAL => 'img::original'
    );

    protected $config_file;

    protected $name = 'ModifiedConfig';

    public function __construct($config_file)
    {
        $this->config_file = $config_file;
    }

    public function beforeRead()
    {
        if (!Tool::is_file($this->config_file)) {
            throw new ConfigException(sprintf('Unable to load modified configuration file "%s"', $this->config_file), 100);
        }
        require_once $this->config_file;
        $keys = $this->getConfigKeys();
        $data = array();
        if (!empty($keys)) {
            foreach ($keys as $key => $value) {
                $s = null;
                if (defined($value)) {
                    $s = constant($value);
                }
                if (strpos($this->trans[$value], self::DELIMITER) !== false) { // Special Key
                    $ret = explode(self::DELIMITER, $this->trans[$value], 2);
                    if (!empty($ret) && is_array($ret)) {
                        if (!isset($data[$ret[0]])) {
                            $data[$ret[0]] = array();
                        }
                        $data[$ret[0]][$ret[1]] = $s;
                    }
                }
                else { // Default Key
                    $data[$this->trans[$value]] = $s;
                }
            }
            if (array_key_exists($this->trans[self::CFG_SHOP_PATH], $data)) {
                $data[$this->trans[self::CFG_SHOP_PATH]] = realpath($data[$this->trans[self::CFG_SHOP_PATH]]);
            }
        }
        $this->data = $data;
    }

    public function getConfigKeys()
    {
        $ecrc = new \ReflectionClass(get_called_class());
        $consts = $ecrc->getConstants();
        $keys = array();
        foreach ($consts as $key => $value) {
            if (substr($key, 0, 3) === 'CFG') {
                $keys[$key] = $value;
            }
        }
        return $keys;
    }

}