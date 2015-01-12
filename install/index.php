<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JTL XTC Connector Installation</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
    <style type="text/css">
        body {
            background: #CCCCCC;
        }
    </style>
  </head>
  <body>
    <div class="container">
        <br>
        <br>
        <div class="panel panel-primary">
            <div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-transfer"></span> JTL-Connector configuration for Modified Shop</h3></div>
            <div class="panel-body">
                <form class="form-horizontal" role="form" method="post">
                    <?php
                        define('CONNECTOR_DIR',realpath(__DIR__ . '/../'));
                        require_once(CONNECTOR_DIR."/vendor/autoload.php");

                        use \jtl\Connector\Modified\Installer\Installer;

                        $installer = new Installer();
                    ?>
                </form>
            </div>
        </div>
    </div>

    <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        $(function() {
            var url = document.location.toString();

            if(url.match('#')) {
                $('.nav-tabs a[href=#'+url.split('#')[1]+']').tab('show') ;
            }

            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                window.location.hash = e.target.hash;
            });
        });
    </script>
  </body>
</html>