<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Session\SessionHelper;
use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Identity;

class BaseMapper
{
    protected $db;
    protected $mapperConfig;
    protected $shopConfig;
    protected $connectorConfig;
    protected $type;
    protected $model;

    public function __construct()
    {
        $session = new SessionHelper("modified");
        $reflect = new \ReflectionClass($this);

        $this->db = Mysql::getInstance();
        $this->shopConfig = $session->shopConfig;
        $this->connectorConfig = $session->connectorConfig;
        $this->model = "\\jtl\\Connector\\Model\\".$reflect->getShortName();
        $this->type = null;
    }

    /**
     * Generate model from db data
     * @param  array  $data
     * @return object
     */
    public function generateModel($data)
    {
        $model = new $this->model();
        if (!$this->type) {
            $this->type = $model->getModelType();
        }

        foreach ($this->mapperConfig['mapPull'] as $host => $endpoint) {
            $value = null;

            if (!$this->type->getProperty($host)) {
                throw new \Exception("Property ".$host." not found");
            }

            if ($this->type->getProperty($host)->isNavigation()) {
                list($endpoint, $setMethod) = explode('|', $endpoint);

                $subMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\".$endpoint;

                if (!class_exists($subMapperClass)) {
                    throw new \Exception("There is no mapper for ".$endpoint);
                } else {
                    if (!method_exists($model, $setMethod)) {
                        throw new \Exception("Set method ".$setMethod." does not exists");
                    }

                    $subMapper = new $subMapperClass();

                    $values = $subMapper->pull($data);

                    foreach ($values as $obj) {
                        $model->$setMethod($obj);
                    }
                }
            } else {
                if (isset($data[$endpoint])) {
                    $value = $data[$endpoint];
                } elseif (method_exists(get_class($this), $host)) {
                    $value = $this->$host($data);
                } else {
                    $value = '';
                }

                if ($this->type->getProperty($host)->isIdentity()) {
                    $value = new Identity($value);
                } else {
                    $type = $this->type->getProperty($host)->getType();

                    if ($type == "DateTime" && !is_null($value)) {
                        $value = $value == '0000-00-00 00:00:00' ? new \DateTime() : new \DateTime($value);
                    } else {
                        settype($value, $type);
                    }
                }

                $setMethod = 'set'.ucfirst($host);
                $model->$setMethod($value);
            }
        }

        if (method_exists(get_class($this), 'addData')) {
            $this->addData($model, $data);
        }

        return $model;
    }

    /**
     * map from model to db object
     * @param  unknown        $data
     * @param  string         $parentDbObj
     * @throws \Exception
     * @return multitype:NULL
     */
    public function generateDbObj($data, $parentDbObj, $parentObj = null, $addToParent = false)
    {
        $return = [];
        if (!is_array($data)) {
            $data = array($data);
        }

        foreach ($data as $obj) {
            $subMapper = [];

            $model = new $this->model();

            if (!$this->type) {
                $this->type = $model->getModelType();
            }

            $dbObj = new \stdClass();

            foreach ($this->mapperConfig['mapPush'] as $endpoint => $host) {
                if (is_null($host) && method_exists(get_class($this), $endpoint)) {
                    $fnValue = $this->$endpoint($obj, $model, $parentObj);
                    if (!is_null($fnValue)) {
                        $dbObj->$endpoint = $fnValue;
                    }
                } elseif ($this->type->getProperty($host)->isNavigation()) {
                    list($preEndpoint, $preNavSetMethod, $preMapper) = array_pad(explode('|', $endpoint), 3, null);

                    if ($preMapper) {
                        $preSubMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\".$preEndpoint;

                        if (!class_exists($preSubMapperClass)) {
                            throw new \Exception("There is no mapper for ".$host);
                        } else {
                            $preSubMapper = new $preSubMapperClass();

                            $values = $preSubMapper->push($obj, $dbObj);

                            if (!is_null($values) && is_array($values)) {
                                foreach ($values as $setObj) {
                                    $model->$preNavSetMethod($setObj);
                                }
                            }
                        }
                    } else {
                        $subMapper[$endpoint] = $host;
                    }
                } else {
                    $value = null;

                    $getMethod = 'get'.ucfirst($host);
                    $setMethod = 'set'.ucfirst($host);

                    if (isset($obj) && method_exists($obj, $getMethod)) {
                        $value = $obj->$getMethod();
                    } else {
                        throw new \Exception("Cannot call get method '".$getMethod."' in entity '".$this->model."'");
                    }

                    if (!is_null($value)) {
                        if ($this->type->getProperty($host)->isIdentity()) {
                            $model->$setMethod($value);

                            $idVal = $value->getEndpoint();

                            if (!empty($idVal)) {
                                $value = $idVal;
                            }
                        } else {
                            $type = $this->type->getProperty($host)->getType();
                            if ($type == "DateTime") {
                                $value = $value->format('Y-m-d H:i:s');
                            } elseif ($type == "boolean") {
                                settype($value, "integer");
                            }
                        }

                        $dbObj->$endpoint = $value;
                    } else {
                        //throw new \Exception("There is no property or method to map ".$endpoint);
                    }
                }
            }

            if (!$addToParent) {
                $whereKey = null;
                $whereValue = null;

                if (isset($this->mapperConfig['where'])) {
                    $whereKey = $this->mapperConfig['where'];
                    
                    if (is_array($whereKey)) {
                        $whereValue = [];
                        foreach ($whereKey as $key) {
                            $whereValue[] = $dbObj->{$key};
                        }
                    } else {
                        $whereValue = $dbObj->{$whereKey};
                    }
                }

                $checkEmpty = get_object_vars($dbObj);

                if (!empty($checkEmpty)) {
                    if (isset($this->mapperConfig['identity'])) {
                        $currentId = $obj->{$this->mapperConfig['identity']}()->getEndpoint();
                    }                    

                    if (!empty($currentId)) {
                        $insertResult = $this->db->updateRow($dbObj, $this->mapperConfig['table'], $whereKey, $whereValue);
                        $insertResult->setKey($currentId);
                    } else {                    
                        $insertResult = $this->db->deleteInsertRow($dbObj, $this->mapperConfig['table'], $whereKey, $whereValue);
                    }

                    if (isset($this->mapperConfig['identity'])) {
                        $obj->{$this->mapperConfig['identity']}()->setEndpoint($insertResult->getKey());
                    }
                }

                if (method_exists(get_class($this), 'pushDone')) {
                    $this->pushDone($model, $dbObj);
                }
            } else {
                foreach ($dbObj as $key => $value) {
                    $parentDbObj->$key = $value;
                }
            }

            foreach ($subMapper as $endpoint => $host) {
                list($endpoint, $navSetMethod) = explode('|', $endpoint);

                $subMapperClass = "\\jtl\\Connector\\Modified\\Mapper\\".$endpoint;

                if (!class_exists($subMapperClass)) {
                    throw new \Exception("There is no mapper for ".$host);
                } else {
                    $subMapper = new $subMapperClass();

                    $values = $subMapper->push($obj);

                    if(!is_null($values) && is_array($values)) {
                        foreach ($values as $setObj) {
                            $model->$navSetMethod($setObj);
                        }
                    }
                }
            }

            $return[] = $model;
        }

        if (is_null($parentObj)) {
            return count($return) > 1 ? $return : $return[0];
        } else {
            return is_array($data) ? $return : $return[0];
        }
    }

    /**
     * Default pull method
     * @param  array   $data
     * @param  integer $offset
     * @param  integer $limit
     * @return array
     */
    public function pull($parentData = null, $limit = null)
    {
        $limitQuery = isset($limit) ? ' LIMIT '.$limit : '';

        if (isset($this->mapperConfig['query'])) {
            if (!is_null($parentData)) {
                $query = preg_replace_callback(
                    '/\[\[(\w+)\]\]/',
                    function ($match) use ($parentData) {
                        return $parentData[$match[1]];
                    },
                    $this->mapperConfig['query']
                );
            } else {
                $query = $this->mapperConfig['query'];
            }

            $query .= $limitQuery;
        } else {
            $query = 'SELECT * FROM '.$this->mapperConfig['table'].$limitQuery;
        }

        $dbResult = $this->db->query($query);

        $return = array();

        foreach ($dbResult as $data) {
            $return[] = $this->generateModel($data);
        }

        return $return;
    }

    /**
     * Default push method
     * @param  unknown        $data
     * @param  string         $dbObj
     * @return multitype:NULL
     */
    public function push($data, $dbObj = null)
    {
        $parent = null;

        if (isset($this->mapperConfig['getMethod'])) {
            $subGetMethod = $this->mapperConfig['getMethod'];
            $parent = $data;
            $data = $data->$subGetMethod();
        }

        $return = $this->generateDbObj($data, $dbObj, $parent);

        return $return;
    }

    /**
     * Default delete method
     * @param  unknown        $data
     * @return multitype:NULL
     */
    public function delete($data)
    {
    }

    /**
     * Default statistics
     * @return number
     */
    public function statistic()
    {
        if (isset($this->mapperConfig['statisticsQuery'])) {
            $result = $this->db->query($this->mapperConfig['statisticsQuery']);
            return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
        } elseif (isset($this->mapperConfig['query'])) {
            $result = $this->db->query($this->mapperConfig['query']);
            return count($result);
        } else {
            $objs = $this->db->query("SELECT count(*) as count FROM {$this->mapperConfig['table']} LIMIT 1",
                array("return" => "object"));
        }

        return $objs !== null ? intval($objs[0]->count) : 0;
    }

    /**
     * Get full locale by ISO code
     * @param  string   $country
     * @return Ambigous <NULL, multitype:string , string>
     */
    public function fullLocale($country)
    {
        return Language::convert($country);
    }

    /**
     * get modified language id by ISO code
     * @param string $locale
     */
    public function locale2id($locale)
    {
        $iso2 = Language::convert(null, $locale);
        $dbResult = $this->db->query('SELECT languages_id FROM languages WHERE code="'.$iso2.'"');

        return $dbResult[0]['languages_id'];
    }

    /**
     * get full locale by modified language id
     * @param  unknown                                 $id
     * @return \jtl\Connector\Modified\Mapper\Ambigous
     */
    public function id2locale($id)
    {
        $dbResult = $this->db->query('SELECT code FROM languages WHERE languages_id="'.$id.'"');

        return $this->fullLocale($dbResult[0]['code']);
    }

    /**
     * Replace 0 value with empty string
     * @param $data
     * @return Ambigous <string, unknown>
     */
    public function replaceZero($data)
    {
        return ($data == 0) ? '' : $data;
    }

    /**
     * Get modified customer groups
     * @return Ambigous <NULL, number, boolean, multitype:multitype: , multitype:unknown >
     */
    public function getCustomerGroups()
    {
        return $this->db->query("SELECT customers_status_id FROM customers_status GROUP BY customers_status_id ORDER BY customers_status_id");
    }

    /**
     * Create a new identity
     * @param  int                           $id
     * @return \jtl\Connector\Model\Identity
     */
    public function identity($id)
    {
        return new Identity($id);
    }

    /**
     * @param $endpointId
     * @param $table
     * @param $imageColumn
     * @param $whereColumn
     * @return mixed|string
     */
    protected function getDefaultColumnImageValue($endpointId, $table, $imageColumn, $whereColumn)
    {
        $image = '';
        if (!empty($endpointId)) {
            $dbImage = $this->db->query(
                sprintf('SELECT %s FROM %s WHERE %s = %s', $imageColumn, $table, $whereColumn, $endpointId)
            );

            if (isset($dbImage[0][$imageColumn])) {
                $image = $dbImage[0][$imageColumn];
            }
        }
        return $image;
    }
}
