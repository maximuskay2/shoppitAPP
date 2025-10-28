#!/usr/bin/env bash

echo "Running composer"
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Optimizing application..."
php artisan optimize