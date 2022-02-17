<?php

namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\AbstractModule;

class ThumbMode extends AbstractModule
{
    public static $name = '<span class="glyphicon glyphicon-picture"></span> Thumbnails';

    public function form(): string
    {
        return '<div class="form-group">
            <label for="thumbs" class="col-xs-2 control-label">Thumbnail-Modus</label>
                <div class="col-xs-6">
                    <select class="form-control" name="thumbs" id="thumbs">
                        <option value="fit"' . ($this->config->thumbs !== 'fit' ?: 'selected') . '>Einpassen</option>
                        <option value="fill"' . ($this->config->thumbs !== 'fill' ?: 'selected') . '>Füllen</option>
                    </select>
                    <span id="helpBlock" class="help-block">
                        Bitte wählen Sie den Berechnungs-Modus zur Erzeugung von Thumbnails. Mittels <b>Einpassen</b> werden keine Teile des Bildes abgeschnitten, jedoch werden Ränder erzeugt wenn das Seitenverhältnis zwischen Quell- und Zielformat nicht übereinstimmen. Mit der Option <b>Füllen</b> werden die Miniaturansichten grundsätzlich vollflächig berechnet, gegebenfalls werden jedoch Teile des Bildes abgeschnitten wenn das Seitenverhältnis nicht übereinstimmt.
                    </span>
                </div>
        </div>';
    }

    public function save(): bool
    {
        $this->config->thumbs = $_REQUEST['thumbs'];

        return true;
    }
}
