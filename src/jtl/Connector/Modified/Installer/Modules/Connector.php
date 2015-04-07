<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Connector extends Module
{
    public static $name = '<span class="glyphicon glyphicon-transfer"></span> Connector configuration';

    public function form()
    {
        if (is_null($this->config->platform_root)) {
            $this->config->platform_root = realpath(CONNECTOR_DIR.'/../');
        }
        if (is_null($this->config->auth_token)) {
            $this->config->auth_token = substr(sha1(uniqid()), 0, 16);
        }

        $html = '
            <div class="form-group">
                <label class="control-label col-xs-2">Platform root</label>
                <div class="col-xs-10">
                    <input type="text" class="form-control" name="config[platform_root]" value="'.$this->config->platform_root.'"/>
                    <span id="helpBlock" class="help-block">
                        Please enter the server path to your modified shop root folder. If you installed the connector into the default "jtlconnector" folder within your shop folder, this should be detected automatically.
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-xs-2">Auth token</label>
                <div class="col-xs-10">
                    <input type="text" class="form-control" name="config[auth_token]" value="'.$this->config->auth_token.'" readonly/>
                    <span id="helpBlock" class="help-block">
                        This is the auth token which was generated for the connector. You need this to setup the connector in the JTL Wawi. You should always keep this token secret and make sure that the config.json file is not public readable.
                    </span>
                </div>
            </div>';

        return $html;
    }

    public function save()
    {
        $this->config->platform_root = $_REQUEST['config']['platform_root'];
        $this->config->auth_token = $_REQUEST['config']['auth_token'];

        return true;
    }
}
