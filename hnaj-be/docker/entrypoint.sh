#!/bin/sh
set -eu

mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

if [ ! -d /var/lib/mysql/mysql ]; then
  mysqld --initialize-insecure --user=mysql --datadir=/var/lib/mysql
fi

mysqld --user=mysql --datadir=/var/lib/mysql --bind-address=0.0.0.0 &
mysql_pid=$!
trap 'kill "$mysql_pid" 2>/dev/null || true' INT TERM EXIT

until mysqladmin ping --silent; do
  sleep 1
done

mysql -uroot <<-SQL
  CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-hnaj}\`;
  CREATE USER IF NOT EXISTS '${DB_USERNAME:-hnaj}'@'%' IDENTIFIED BY '${DB_PASSWORD:-secret}';
  GRANT ALL PRIVILEGES ON \`${DB_DATABASE:-hnaj}\`.* TO '${DB_USERNAME:-hnaj}'@'%';
  FLUSH PRIVILEGES;
SQL

if [ -f artisan ]; then
  if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
  fi

  if [ ! -f vendor/autoload.php ] && [ -f composer.json ]; then
    composer install --no-interaction --prefer-dist
  fi

  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
  fi

  php artisan migrate --force
  php artisan serve --host=0.0.0.0 --port=8000 &
  app_pid=$!
  wait "$app_pid"
else
  wait "$mysql_pid"
fi
