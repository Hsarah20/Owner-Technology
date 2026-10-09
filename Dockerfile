#syntax=docker/dockerfile:1.4

ARG PHP_VERSION=8.3
ARG CADDY_VERSION=2.8

# ==========================================================
# Stage PHP-FPM (base prod)
# ==========================================================
FROM php:${PHP_VERSION}-fpm-alpine AS app_php

ENV APP_ENV=prod

WORKDIR /srv/app

RUN apk add --no-cache \
        acl file gettext git make bash fcgi \
        icu-dev libzip-dev postgresql-dev oniguruma-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) intl zip pdo pdo_pgsql opcache \
    && pecl install apcu && docker-php-ext-enable apcu \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN mkdir -p /var/run/php \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

COPY composer.json composer.lock symfony.lock ./
RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress \
    && composer clear-cache

COPY . .
RUN rm -rf docker/ \
    && mkdir -p var/cache var/log var/storage \
    && composer dump-autoload --classmap-authoritative --no-dev \
    && composer dump-env prod \
    && chmod +x bin/console

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

# ==========================================================
# Stage DEV
# ==========================================================
FROM app_php AS app_php_dev

ENV APP_ENV=dev XDEBUG_MODE=off

RUN rm -f $PHP_INI_DIR/conf.d/app.prod.ini \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug && docker-php-ext-enable xdebug \
    && apk del .build-deps \
    && rm -f .env.local.php

COPY docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

# ==========================================================
# Stage Caddy (sans Mercure/Vulcain)
# ==========================================================
FROM caddy:${CADDY_VERSION}-alpine AS app_caddy

WORKDIR /srv/app

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=app_php /srv/app/public public/