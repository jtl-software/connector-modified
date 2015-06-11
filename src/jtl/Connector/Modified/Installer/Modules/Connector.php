<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Connector extends Module
{
    public static $name = '<span class="glyphicon glyphicon-transfer"></span> Connector Konfiguration';

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
                <label class="control-label col-xs-2">Connector URL</label>
                <div class="col-xs-10">
                    <input type="text" class="form-control" name="config[auth_token]" value="'.$this->shopConfig['shop']['fullUrl'].'jtlconnector/" readonly/>
                    <span id="helpBlock" class="help-block">
                        Dies ist die URL die in Ihrer Wawi zum einrichten des Connectors verwendet werden muss.
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-xs-2">Passwort</label>
                <div class="col-xs-10">
                    <input type="text" class="form-control" name="config[auth_token]" value="'.$this->config->auth_token.'" readonly/>
                    <span id="helpBlock" class="help-block">
                        Dies ist das Passwort welches für den Connector generiert wurde. Sie benötigen dieses um den Connector in Ihrer Wawi einzurichten. Bitte achten Sie darauf das Passwort sicher aufzubewahren und die config.json Datei und den Installer nicht öffentlich zugänglich zu machen.
                    </span>
                </div>
            </div>';

        return $html;
    }

    public function save()
    {
        $this->config->auth_token = $_REQUEST['config']['auth_token'];

        return true;
    }
}
