<?php

namespace jtl\Connector\Modified\Controller;

use Jtl\Connector\XtcComponents\AbstractController;

/**
 * Class AbstractController
 * @package jtl\Connector\Modified\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return 'jtl\\Connector\\Modified';
    }
}
