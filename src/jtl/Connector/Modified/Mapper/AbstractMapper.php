<?php

namespace jtl\Connector\Modified\Mapper;

use Jtl\Connector\XtcComponents\AbstractBaseMapper;

/**
 * Class AbstractMapper
 * @package jtl\Connector\Modified\Mapper
 */
abstract class AbstractMapper extends AbstractBaseMapper
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
