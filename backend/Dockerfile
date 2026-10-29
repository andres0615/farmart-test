# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.3

# ---------------------------------------------------------------
# Etapa 1: instala las dependencias de producción (una sola vendor/)
# ---------------------------------------------------------------
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
    --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------------------------------------------------------------
# Etapa 2: imagen final con PHP-FPM + Nginx + Supervisor (no root)
# ---------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS app

COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache nginx supervisor wget \
    && install-php-extensions pdo_pgsql pgsql opcache bcmath pcntl \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app

COPY docker/php/custom.ini   $PHP_INI_DIR/conf.d/99-custom.ini
COPY docker/php/zzz-app.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
COPY --from=deps --chown=app:app /app /var/www/html

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions \
                storage/framework/views storage/logs bootstrap/cache \
    && chown -R app:app storage bootstrap/cache

USER app
EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=5 \
    CMD wget -qO- http://127.0.0.1:8080/health || exit 1

# ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]