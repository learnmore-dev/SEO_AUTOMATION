FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" curl fileinfo gd mbstring mysqli pdo_mysql xml zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

RUN printf '%s\n' \
        'date.timezone=Asia/Kolkata' \
        'max_execution_time=300' \
        'memory_limit=512M' \
        'post_max_size=64M' \
        'upload_max_filesize=64M' \
        > /usr/local/etc/php/conf.d/app.ini \
    && mkdir -p /var/www/html/data /var/www/html/uploads /var/www/html/logs \
    && printf '%s\n' '<?php' 'return [];' > /var/www/html/config.local.php \
    && chown www-data:www-data \
        /var/www/html/config.local.php \
        /var/www/html/data \
        /var/www/html/uploads \
        /var/www/html/logs

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r '$c=curl_init("http://127.0.0.1/"); curl_setopt($c,CURLOPT_RETURNTRANSFER,true); exit(curl_exec($c)===false?1:0);'
