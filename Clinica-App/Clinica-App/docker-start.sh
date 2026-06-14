#!/bin/bash

set -e

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan db:seed --force || true
php artisan storage:link || true

apache2-foreground