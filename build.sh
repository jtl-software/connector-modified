#!/bin/bash
ulimit -n 100000;
<<<<<<< HEAD
rm ./vendor -rf;
composer update --no-dev;
php ./vendor/bin/phing release;
=======
composer update --no-dev;
php ./phing.phar package;
>>>>>>> feature/CO-761_custom_image_name
composer update;