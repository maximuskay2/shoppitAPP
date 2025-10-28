#!/usr/bin/env bash
# Navigate to the application directory
cd /var/www/html

echo "Running composer"
composer install --no-dev --optimize-autoloader

echo "Clearing cached data"
php artisan optimize:clear

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force