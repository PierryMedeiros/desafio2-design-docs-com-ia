#!/usr/bin/env bash
# deploy na VPS: ./scripts/deploy.sh
set -e

SERVIDOR=${SERVIDOR:-deploy@vps1.horalis.example}
DIR=/var/www/horalis

ssh "$SERVIDOR" bash -s <<EOF
set -e
cd $DIR
php artisan down
git pull
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan tenants:migrate
php artisan config:cache
php artisan route:cache
php artisan up
sudo systemctl reload php7.3-fpm
EOF
