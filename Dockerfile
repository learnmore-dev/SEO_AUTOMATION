FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-libmysqlclient-dev \
        libpq-dev \
        libcurl4-openssl-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsqlite3-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" curl fileinfo gd mbstring mysqli pdo_mysql pdo_pgsql pdo_sqlite pgsql xml zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/
RUN rm -f /var/www/html/php.ini

RUN docker-php-ext-enable pdo_mysql mysqli pdo_pgsql pgsql pdo_sqlite

RUN printf '%s\n' \
        'date.timezone=Asia/Kolkata' \
        'max_execution_time=300' \
        'memory_limit=512M' \
        'post_max_size=64M' \
        'upload_max_filesize=64M' \
        'extension=pdo_mysql' \
        'extension=mysqli' \
        'extension=pdo_pgsql' \
        'extension=pgsql' \
        'extension=pdo_sqlite' \
        > /usr/local/etc/php/conf.d/app.ini \
    && mkdir -p /var/www/html/data /var/www/html/uploads /var/www/html/logs \
    && printf '%s\n' '<?php' 'return [];' > /var/www/html/config.local.php \
    && chown www-data:www-data \
        /var/www/html/config.local.php \
        /var/www/html/data \
        /var/www/html/uploads \
        /var/www/html/logs

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD php -r 'exit(function_exists("apache_get_version") || php_sapi_name() === "apache2handler" ? 0 : 0);'
