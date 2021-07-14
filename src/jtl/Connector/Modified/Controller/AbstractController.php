<?php

namespace jtl\Connector\Modified\Controller;

/**
 * Class AbstractController
 * @package jtl\Connector\Modified\Controller
 */
abstract class AbstractController extends \Jtl\Connector\XtcComponents\AbstractController
{
    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return 'jtl\\Connector\\Modified';
    }
}
