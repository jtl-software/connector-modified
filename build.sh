#!/bin/bash
ulimit -n 100000;
rm ./vendor -rf;
composer update --no-dev;
php ./vendor/bin/phing release;
composer update;