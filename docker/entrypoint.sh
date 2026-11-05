#!/bin/sh
set -e
cd /var/www/html

[ -f .env ] || cp .env.example .env
[ -d vendor ] || composer install --no-interaction --prefer-dist
grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force

echo "Esperando a MySQL..."
until php -r 'try{new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),getenv("DB_USERNAME"),getenv("DB_PASSWORD"));}catch(Exception $e){exit(1);}' ; do
  sleep 2
done

php artisan migrate --force
[ "$SEED_DEMO" = "true" ] && php artisan db:seed --force || true

chown -R www-data:www-data storage bootstrap/cache
exec "$@"
