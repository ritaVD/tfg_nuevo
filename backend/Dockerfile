# ── Symfony backend — PHP 8.2 FPM + Nginx (Railway-compatible) ────────────────
FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
        git \
        unzip \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        mysql-client \
        nginx \
        supervisor \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
        opcache \
        mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock symfony.lock ./

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist

COPY . .

RUN COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=prod composer dump-autoload \
        --optimize \
        --no-dev

RUN echo "APP_ENV=prod" > .env.local

RUN mkdir -p var/cache var/log \
    && mkdir -p public/uploads/avatars public/uploads/posts \
    && chown -R www-data:www-data var/ public/uploads/

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

COPY docker/nginx-railway.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/zz-fpm.conf /usr/local/etc/php-fpm.d/zz-fpm.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080
CMD ["/entrypoint.sh"]
