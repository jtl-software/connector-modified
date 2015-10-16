<?php
namespace jtl\Connector\Modified\Auth;

use \jtl\Connector\Authentication\ITokenLoader;

class TokenLoader implements ITokenLoader
{
    public function load()
    {
        return Application()->getConfig()->read('auth_token');
    }
}
