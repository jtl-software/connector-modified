<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JTL XTC Connector Installation</title>

    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <style type="text/css">
        body {
            background:
                radial-gradient(black 15%, transparent 16%) 0 0,
                radial-gradient(black 15%, transparent 16%) 8px 8px,
                radial-gradient(rgba(255,255,255,.1) 15%, transparent 20%) 0 1px,
                radial-gradient(rgba(255,255,255,.1) 15%, transparent 20%) 8px 9px;
                background-color:#282828;
                background-size:16px 16px;
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
                        include('../index.php');

                        use \jtl\Connector\Modified\Installer\Installer;

                        $installer = new Installer();
                    ?>
                </form>
            </div>
        </div>
    </div>

    <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
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