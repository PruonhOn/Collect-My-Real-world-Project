# syntax=docker/dockerfile:1

###############################################################################
# PHP runtime for the Logistics ERP API.
#
# One image, three roles: php-fpm behind Nginx, the queue worker and the
# scheduler. The application source is bind mounted at run time, so editing a
# controller is picked up by all three without a rebuild - only the toolchain
# and the extension set are baked in here.
#
# Alpine rather than Debian because the image is otherwise identical in
# behaviour and roughly a third of the size. The native-extension risk that
# Alpine usually carries does not apply: the PHP side has no prebuilt binaries,
# and Redis is reached through predis (pure PHP) rather than ext-redis, which
# would need a PECL build.
###############################################################################

ARG PHP_VERSION=8.3

# Composer as a single-file binary from its own image, so the runtime image
# never carries composer's own dependency tree.
FROM composer:2 AS composer-bin

FROM php:${PHP_VERSION}-fpm-alpine

# Superuser is required because the bind-mounted project files are owned by the
# host user, which is uid 0 from the container's point of view.
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Build toolchain plus the headers the extensions below compile against, in a
# single apk call, then dropped again in the same layer. Splitting the install
# and the delete into two RUN steps leaves the -dev packages in an earlier layer
# where they stay in the image even though apk no longer lists them.
#
# The extension list is the exact set composer.lock demands:
#   phpoffice/phpspreadsheet -> dom, fileinfo, gd, iconv, libxml, mbstring,
#                               simplexml, xml, xmlreader, xmlwriter, zip, zlib
#   phpunit / laravel/pint   -> dom, libxml, xml, xmlwriter, mbstring, tokenizer
#   laravel/framework        -> ctype, filter, hash, mbstring, openssl, session
# plus pdo_mysql / pdo_sqlite for MySQL and the in-memory test database, intl
# for date and number formatting, and pcntl for the queue worker and scheduler.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libxml2-dev \
        libzip-dev \
        zlib-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libfreetype-dev \
    && apk add --no-cache \
        icu-libs \
        libpng \
        libjpeg-turbo \
        libwebp \
        libfreetype \
        libzip \
        tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && apk del --no-network .build-deps

# opcache is the one extension where the shipped .ini defaults are wrong for
# this workload: memory_consumption of 128M is not enough for a framework this
# size and validate_timestamps defaults to a 2 second revalidate window, which
# shows up as a stale worker holding a deleted class file.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-logistics.ini

# clear_env = no is mandatory, not a tuning choice. php-fpm strips the process
# environment by default, which would hide the compose-provided DB_HOST and
# leave every container reading the host's backend/.env - including its
# Windows DB_DATABASE path.
COPY docker/php/php-fpm.d/zz-logistics.conf /usr/local/etc/php-fpm.d/zz-logistics.conf

COPY --from=composer-bin /composer /usr/local/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
