FROM composer:2.8.12 AS composer

FROM php:8.2.34-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql pgsql zip pcntl bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/horalis.ini /usr/local/etc/php/conf.d/horalis.ini

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php-fpm"]
