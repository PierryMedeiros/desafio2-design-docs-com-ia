#!/bin/sh
set -e

if [ "$1" = "php-fpm" ]; then
    rm -f /tmp/horalis-pronto

    until php docker/aguardar-banco.php; do
        echo "aguardando o postgres..."
        sleep 2
    done

    php artisan migrate --force
    php artisan db:seed --force

    touch /tmp/horalis-pronto
fi

exec "$@"
