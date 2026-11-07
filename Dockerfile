
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
 add-docker-support
    && docker-php-ext-install -j"$(nproc)" curl fileinfo gd mbstring mysqli pdo_mysql pdo_pgsql pdo_sqlite pgsql xml zip \

    && docker-php-ext-install -j"$(nproc)" curl fileinfo gd mbstring mysqli pdo_mysql xml zip \ main
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/
RUN rm -f /var/www/html/php.ini

RUN docker-php-ext-enable pdo_mysql mysqli pdo_pgsql pgsql pdo_sqlite

# PHP configuration
RUN printf '%s\n' \
        'date.timezone=Asia/Kolkata' \
        'max_execution_time=300' \
        'memory_limit=512M' \
        'post_max_size=64M' \
        'upload_max_filesize=64M' \
         add-docker-support
        'extension=pdo_mysql' \
        'extension=mysqli' \
        'extension=pdo_pgsql' \
        'extension=pgsql' \
        'extension=pdo_sqlite' \
        > /usr/local/etc/php/conf.d/app.ini \
    && mkdir -p /var/www/html/data /var/www/html/uploads /var/www/html/logs \

        > /usr/local/etc/php/conf.d/app.ini

# Apache configuration for Cloud Run
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

# Application directories and permissions
RUN mkdir -p /var/www/html/data /var/www/html/uploads /var/www/html/logs \

    && printf '%s\n' '<?php' 'return [];' > /var/www/html/config.local.php \
    && chown -R www-data:www-data \
        /var/www/html/data \
        /var/www/html/uploads \
        /var/www/html/logs \
        /var/www/html/config.local.php

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD php -r 'exit(function_exists("apache_get_version") || php_sapi_name() === "apache2handler" ? 0 : 0);'
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r '$c=curl_init("http://127.0.0.1:8080/"); curl_setopt($c,CURLOPT_RETURNTRANSFER,true); exit(curl_exec($c)===false?1:0);'

CMD ["apache2-foreground"]

