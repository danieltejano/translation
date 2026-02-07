#!/bin/sh

echo "Listening for Database"

while ! nc -z $DB_HOST 5432; do 
    sleep 0.1
done

mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/bootstrap/cache

# Change ownership to the web user
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Connected to $DB_CONNECTION Database with $DB_USERNAME $DB_PASSWORD"

echo "Container is Running Migrations..."

php artisan migrate --seed --force
php artisan optimize:clear
php artisan optimize 

echo "Starting Php-FPM..."

exec php-fpm