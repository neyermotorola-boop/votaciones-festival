#!/bin/sh
set -e
cd /var/www/html

esperar_mysql() {
  echo "Esperando a MySQL..."
  until php -r 'try{new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),getenv("DB_USERNAME"),getenv("DB_PASSWORD"));}catch(Exception $e){exit(1);}' ; do
    sleep 2
  done
}

# El worker de colas solo espera a que la app termine de prepararse.
if [ "$CONTAINER_ROLE" = "worker" ]; then
  until [ -f vendor/autoload.php ] && [ -f .env ] && grep -q '^APP_KEY=.\+' .env; do
    echo "Esperando a que la aplicación instale dependencias..."
    sleep 3
  done
  esperar_mysql
  until php artisan migrate:status >/dev/null 2>&1; do sleep 3; done
  exec "$@"
fi

[ -f .env ] || cp .env.example .env
# vendor es un volumen: puede existir vacío, por eso se revisa autoload.php.
[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist --optimize-autoloader
grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force

esperar_mysql

php artisan migrate --force
[ "$SEED_DEMO" = "true" ] && php artisan db:seed --force || true

# Carpeta pública para las fotos de participantes.
mkdir -p public/uploads/opciones
chown -R www-data:www-data storage bootstrap/cache public/uploads
exec "$@"
