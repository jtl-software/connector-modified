<?php
namespace jtl\Connector\Modified\Installer\Modules;

use jtl\Connector\Modified\Installer\Module;

class Logs extends Module
{
    public static $name = '<span class="glyphicon glyphicon-transfer"></span> Clear log files';

    public function form()
    {
        $html = '
            <a href="#logs" id="clearBtn" class="btn btn-default btn-block">Clear log files</a>
            <br>
            <script type="text/javascript">
                $(function() {
                    $("#clearBtn").click(function(e) {
                        alert("bla");
                    });
                });
            </script>';

        return $html;
    }

    public function save()
    {
        return true;
    }
}
