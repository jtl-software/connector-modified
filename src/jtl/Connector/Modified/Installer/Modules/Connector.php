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
                <label class="control-label col-xs-2">Platform Verzeichnis</label>
                <div class="col-xs-10">
                    <input type="text" class="form-control" name="config[platform_root]" value="'.$this->config->platform_root.'"/>
                    <span id="helpBlock" class="help-block">
                        Bitte geben Sie hier den Server-Pfad zu Ihrem modified Shop an. Wenn Sie den Connector in das Standard-Verzeichnis "jtlconnector" in Ihrem Shop installiert haben, sollte der Pfad automatisch erkannt werden.
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-xs-2">Auth Token</label>
                <div class="col-xs-10">
                    <input type="text" class="form-control" name="config[auth_token]" value="'.$this->config->auth_token.'" readonly/>
                    <span id="helpBlock" class="help-block">
                        Dies ist der Auth-Token der für den Connector generiert wurde. Sie benötigen diesen um den Connector in Ihrer Wawi einzurichten. Bitte achten Sie darauf den Token sicher aufzubewahren und die config.json Datei und den Installer nicht öffentlich zugänglich zu machen.
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
