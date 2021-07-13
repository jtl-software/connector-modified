<?php

namespace jtl\Connector\Modified\Controller;

use Jtl\Connector\XtcComponents\AbstractBaseController;

abstract class AbstractController extends AbstractBaseController
{
    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return 'jtl\\Connector\\Modified';
    }
}
