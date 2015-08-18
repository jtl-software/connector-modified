<?php
namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ShippingMethod as ShippingMethodModel;

class ShippingMethod extends BaseMapper
{
    protected $mapperConfig = array(
        "identity" => "getId",
        "getMethod" => "getShippingMethods"
    );

    public function pull($data, $limit = null)
    {
        $moduleStr = $this->db->query('SELECT configuration_value FROM configuration WHERE configuration_key ="MODULE_SHIPPING_INSTALLED"');

        if (count($moduleStr) > 0) {
            $modules = explode(';',$moduleStr[0]['configuration_value']);
            if (count($modules) > 0) {
                $return = array();

                foreach ($modules as $moduleFile) {
                    $modName = str_replace('.php', '', $moduleFile);
                    include_once($this->shopConfig['shop']['path'] . 'lang/german/modules/shipping/' . $modName . '.php');

                    if (defined('MODULE_SHIPPING_' . strtoupper($modName) . '_TEXT_TITLE')) {
                        $modTitle = constant('MODULE_SHIPPING_'.strtoupper($modName).'_TEXT_TITLE');
                    } else {
                        $modTitle = $modName;
                    }

                    $model = new ShippingMethodModel();
                    $model->setName($modTitle);
                    $model->setId(new Identity($modName));

                    $return[] = $model;
                }

                return $return;
            }
        }
    }
}
