<?php
namespace jtl\Connector\Modified\Controller;

use jtl\Connector\Model\ConnectorServerInfo;
use jtl\Connector\Modified\Modified;
use jtl\Connector\Result\Action;
use jtl\Connector\Model\ConnectorIdentification;

/**
 * Class Connector
 * @package jtl\Connector\Modified\Controller
 */
class Connector extends AbstractController
{
    /**
     * @return Action
     */
    public function finish()
    {
        $sessionHelper = Modified::getSessionHelper();
        if ($sessionHelper->deleteUnusedVariations === true) {
            $this->db->query('
                DELETE FROM products_options_values
                WHERE products_options_values_id IN (
                    SELECT * FROM (
                        SELECT v.products_options_values_id
                        FROM products_options_values v
                        LEFT JOIN products_attributes a ON v.products_options_values_id = a.options_values_id
                        WHERE a.products_attributes_id IS NULL
                        GROUP BY v.products_options_values_id
                    ) relations
                )
            ');

            $this->db->query('
                DELETE FROM products_options
                WHERE products_options_id IN (
                    SELECT * FROM (
                        SELECT o.products_options_id
                        FROM products_options o
                        LEFT JOIN products_attributes a ON o.products_options_id = a.options_id
                        WHERE a.products_attributes_id IS NULL
                        GROUP BY o.products_options_id
                    ) relations
                )
            ');
            $sessionHelper->deleteUnusedVariations = false;
        }

        return (new Action())
            ->setHandled(true)
            ->setResult(true);
    }

    /**
     * @return Action
     */
    public function identify()
    {
        $action = new Action();
        $action->setHandled(true);

        $config = $this->connectorConfig;

        define('_VALID_XTC', true);

        foreach (new \DirectoryIterator($config->platform_root) as $shopRoot) {
            if (!$shopRoot->isDot() && $shopRoot->isDir() && is_file($config->platform_root.'/'.$shopRoot->getFilename().'/check_update.php')) {
                include($config->platform_root.'/'.$shopRoot->getFilename().'/includes/version.php');
                break;
            }
        }

        $returnMegaBytes = function ($value) {
            $value = trim($value);
            $unit = strtolower($value[strlen($value) - 1]);
            switch ($unit) {
                case 'g':
                    $value *= 1024;
            }

            return (int) $value;
        };

        $serverInfo = new ConnectorServerInfo();
        $serverInfo->setMemoryLimit($returnMegaBytes(ini_get('memory_limit')))
            ->setExecutionTime((int) ini_get('max_execution_time'))
            ->setPostMaxSize($returnMegaBytes(ini_get('post_max_size')))
            ->setUploadMaxFilesize($returnMegaBytes(ini_get('upload_max_filesize')));

        $connector = new ConnectorIdentification();
        $connector->setEndpointVersion(CONNECTOR_VERSION);
        $connector->setPlatformName('modified eCommerce');

        if (defined('PROJECT_MAJOR_VERSION')) {
            $connector->setPlatformVersion(PROJECT_MAJOR_VERSION.'.'.PROJECT_MINOR_VERSION);
        } else {
            $connector->setPlatformVersion('1.0.6');
        }

        $connector->setProtocolVersion(Application()->getProtocolVersion());
        $connector->setServerInfo($serverInfo);

        $action->setResult($connector);

        return $action;
    }
}
