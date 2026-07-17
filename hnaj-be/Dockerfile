FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev mariadb-server \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

COPY docker/entrypoint.sh /usr/local/bin/hnaj-entrypoint
RUN chmod +x /usr/local/bin/hnaj-entrypoint

EXPOSE 8000
EXPOSE 3306
ENTRYPOINT ["hnaj-entrypoint"]
