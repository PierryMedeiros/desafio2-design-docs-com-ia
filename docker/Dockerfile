FROM php:7.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev zip unzip git \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:1.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
