<?php

namespace jtl\Connector\Modified\Util;

/**
 * Class ShopVersion
 * @package jtl\Connector\Modified\Util
 */
class ShopVersion
{
    /**
     * @var
     */
    protected static $shopVersion;

    /**
     * @param string $version
     * @return bool
     * @throws \Exception
     */
    public static function isGreaterOrEqual(string $version): bool
    {
        if (is_null(static::$shopVersion)) {
            throw new \Exception('Shop version is not set');
        }
        return version_compare(static::$shopVersion, $version, '>=');
    }

    /**
     * @param string $version
     */
    public static function setShopVersion(string $version)
    {
        ShopVersion::$shopVersion = $version;
    }
}
