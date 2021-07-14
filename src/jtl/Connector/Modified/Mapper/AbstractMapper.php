<?php

namespace jtl\Connector\Modified\Mapper;

/**
 * Class AbstractMapper
 * @package jtl\Connector\Modified\Mapper
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
