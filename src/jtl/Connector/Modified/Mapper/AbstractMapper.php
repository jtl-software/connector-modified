<?php

namespace jtl\Connector\Modified\Mapper;

use jtl\Connector\Core\Database\Mysql;

/**
 * Class AbstractMapper
 * @package jtl\Connector\Modified\Mapper
 * @property Mysql $db
 */
abstract class AbstractMapper extends \Jtl\Connector\XtcComponents\AbstractMapper
{
    /**
     * @return string
     */
    protected function getShopName(): string
    {
        return "modified";
    }

    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return "jtl\\Connector\\Modified";
    }
}
