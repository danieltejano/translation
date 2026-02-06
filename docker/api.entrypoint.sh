#!/bin/sh

echo "Listening for Database"

while ! nc -z $DB_HOST 5432; do 
    sleep 0.1
done


echo "Connected to $DB_CONNECTION Database"

echo "Container is Running Migrations..."

php artisan optimize:clear && php artisan optimize php artisan migrate && php artisan db:seed

echo "Starting Php-FPM..."

exec php-fpm