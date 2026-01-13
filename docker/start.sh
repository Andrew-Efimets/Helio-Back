#!/bin/sh
set -e

cd /var/www/html

if [ "$1" != "php-fpm" ]; then
    echo "Ожидание инициализации проекта..."
    while [ ! -f "artisan" ]; do
        sleep 2
    done
fi

if [ ! -f "composer.json" ]; then
    echo "Laravel не найден, создаю проект..."
    composer create-project --prefer-dist laravel/laravel . --stability=stable
    chown -R www-data:www-data .
else
    echo "Laravel уже установлен."
fi

if [ ! -d "vendor" ]; then
    composer install
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"