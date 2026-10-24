FROM dunglas/frankenphp:1.13.3-php8.4-bookworm

RUN install-php-extensions pdo_mysql mbstring xml zip opcache
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# Composer dipasang sebelum source code agar Docker layer dapat di-cache.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/start.sh /usr/local/bin/start-laravel
RUN chmod +x /usr/local/bin/start-laravel
EXPOSE 80
CMD ["/usr/local/bin/start-laravel"]
