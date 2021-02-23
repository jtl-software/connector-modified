<?php


namespace jtl\Connector\Modified;


use Jtl\Connector\XtcComponents\AbstractBaseController;

class Controller extends AbstractBaseController
{
    /**
     * @return string
     */
    protected function getMainNamespace(): string
    {
        return 'jtl\\Connector\\Modified';
    }
}