FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev mariadb-server \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN printf 'upload_max_filesize=500M\npost_max_size=512M\n' > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

COPY docker/entrypoint.sh /usr/local/bin/hnaj-entrypoint
RUN chmod +x /usr/local/bin/hnaj-entrypoint

EXPOSE 8000
EXPOSE 3306
ENTRYPOINT ["hnaj-entrypoint"]
