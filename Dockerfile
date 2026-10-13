FROM php:8.3-fpm-bookworm
ARG PHALCON_VERSION=5.22.1
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl ca-certificates libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libpcre2-dev libsqlite3-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_mysql pdo_sqlite mbstring gd \
    && curl -fsSL "https://github.com/phalcon/cphalcon/releases/download/v${PHALCON_VERSION}/phalcon-pecl.tgz" -o /tmp/phalcon.tgz \
    && pecl install /tmp/phalcon.tgz \
    && docker-php-ext-enable phalcon \
    && rm -rf /var/lib/apt/lists/* /tmp/phalcon.tgz
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --optimize-autoloader \
    && mkdir -p var/cache var/session var/log public/uploads/players \
    && chown -R www-data:www-data var public/uploads
COPY docker/php.ini /usr/local/etc/php/conf.d/cantera.ini
COPY docker/entrypoint.sh /usr/local/bin/cantera-entrypoint
RUN chmod +x /usr/local/bin/cantera-entrypoint
ENTRYPOINT ["cantera-entrypoint"]
CMD ["php-fpm"]
