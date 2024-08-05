#!/bin/sh

#php load_fonts.php

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

php artisan telegram:webhook --setup post_bot

php artisan download:adminer

/var/www/html/run-scheduler.sh &

/usr/bin/supervisord --configuration /etc/supervisord.conf
