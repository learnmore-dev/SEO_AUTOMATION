FROM php:8.3-apache

# Cloud Run sends traffic to PORT (default 8080), not 80
ENV PORT=8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        default-libmysqlclient-dev \
        libpq-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsqlite3-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mbstring mysqli pdo_mysql pdo_pgsql pdo_sqlite pgsql xml zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/
RUN rm -f /var/www/html/php.ini

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

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD curl -sf "http://127.0.0.1:8080/" > /dev/null || exit 1

CMD ["apache2-foreground"]
