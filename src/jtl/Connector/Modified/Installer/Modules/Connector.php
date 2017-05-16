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
            </div>
            <div class="form-group">
                <label for="utf8" class="col-xs-2 control-label">UTF8 Konvertierung</label>
                <div class="col-xs-10">
                    <select class="form-control" name="config[utf8]" id="utf8">
                        <option value="0"'.($this->config->utf8 !== '0' ?: 'selected' ) .'>Deaktiviert</option>
                        <option value="1"'.($this->config->utf8 !== '1' ?: 'selected') .'>Aktiviert</option>
                    </select>
                    <span id="helpBlock" class="help-block">
                        Oftmals werden in xt-basierten Shops UTF8 Hacks und Themes verwendet, welche den serienmäßigen Zustand des Systems zugunsten einer zeitgemäßeren Zeichensatz-Kodierung aushebeln. Sollte der Connector nicht funktionieren oder sollten Probleme bei der Darstellung von Umlauten und Sonderzeichen auftreten, aktivieren Sie diese Option bitte.
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-xs-2">Datums-Grenze</label>
                <div class="col-xs-10">
                    <input type="date" class="form-control" name="config[from_date]" value="'.$this->config->from_date.'"/>
                    <span id="helpBlock" class="help-block">
                        Legen Sie hier bei Bedarf eine Datums-Grenze für Bestellungen fest. Wenn diese Einstellung gesetzt ist, werden nur Bestellungen abgeglichen die nach diesem Datum erfolgten.<br>
                        <b>Wenn Sie diese Option verwenden, sollten Sie sicherstellen dass in Ihrer Datenbank-Tabelle "orders" ein Index auf der Spalte "date_purchased" gesetzt ist.</b>
                    </span>
                </div>
            </div>';

        return $html;
    }

    public function save()
    {
        $this->config->auth_token = $_REQUEST['config']['auth_token'];
        $this->config->utf8 = $_REQUEST['config']['utf8'];
        $this->config->from_date = $_REQUEST['config']['from_date'];

        return true;
    }
}
