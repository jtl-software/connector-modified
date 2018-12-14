<?php

namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class DevLogging extends Module
{
    public static $name = '<span class="glyphicon glyphicon-edit"></span> DevLogging';
    
    public function form()
    {
        $html = "";
        $html .= '
        <div>
        <div class="form-group">
            <label for="logging" class="col-xs-2 control-label">Developer Logging</label>
                <div class="col-xs-6">
                    <select class="form-control" name="logging" id="logging">
                        <option value="1"' . ($this->config->developer_logging !== true ? : 'selected') . '>Aktiviert</option>
                        <option value="0"' . ($this->config->developer_logging !== false ? : 'selected') . '>Deaktiviert</option>
                    </select>
                    <span id="helpBlock" class="help-block">
                        Durch aktivieren dieser Option werden im Fehlerfall erweiterte Logdateien geschieben welche bei Supportanfragen für eine schnellere Hilfe erforderlich sind.
                    </span>
                </div>
        </div>
        <div class="form-group">
            <label for="clear" class="col-xs-2 control-label">Logs löschen</label>
            <div class="col-xs-6">
            ';
        
        if (count(scandir($this->config->platform_root . '/jtlconnector/logs')) > 3){
            $html .=' <button formmethod="post" formaction="/jtlconnector/install/loggingConfig.php" name="clear" class="btn btn-default btn-sm btn-block">Clear</button>';
        }else{
            $html .= '
            <div data-toggle="tooltip" data-placement="top" title="Es wurden keine Logs gefunden!">
                <button disabled class="btn btn-default btn-sm btn-block">Clear</button>
            </div>
            
            ';
        }
        
        $html .='
            </div>
        </div>
        <div class="form-group">
            <label for="download" class="col-xs-2 control-label">Logs herunterladen</label>
            <div class="col-xs-6">';
        
        if (count(scandir($this->config->platform_root . '/jtlconnector/logs')) > 3){
            $html .='<button formmethod="post" formaction="/jtlconnector/install/loggingConfig.php" name="download" class="btn btn-default btn-sm btn-block">Download</button>';
        }else{
            $html .= '
            <div data-toggle="tooltip" data-placement="top" title="Es wurden keine Logs gefunden!">
                <button disabled class="btn btn-default btn-sm btn-block">Download</button>
            </div>
            
            ';
        }
            
            $html .= '</div>
        </div>
        </div>';
        
        return $html;
    }
    
    public function save()
    {
        $this->config->developer_logging = (bool)$_REQUEST['logging'];
        
        return true;
    }
}
