<?php

namespace jtl\Connector\Modified\Mapper;

use Jtl\Connector\XtcComponents\AbstractBaseMapper;

/**
 * Class BaseMapper
 * @package jtl\Connector\Modified\Mapper
 */
class BaseMapper extends AbstractBaseMapper
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
    protected function getMapperNamespace(): string
    {
        return "jtl\\Connector\\Modified\\Mapper";
    }
}
