#!/bin/sh
set -e

# Crea el .env dentro del contenedor si no existe.
# if [ ! -f .env ]; then
#   cp .env.example .env
#   chmod 777 .env
# fi

# if [ -z "$APP_KEY" ]; then
#   echo "ERROR: APP_KEY no está definida. Genérala con 'php artisan key:generate --show'." >&2
#   exit 1
# fi

php artisan key:generate

php artisan package:discover --ansi
# borrar cache
php artisan optimize:clear

php artisan jwt:secret

# Solo un contenedor debe migrar (RUN_MIGRATIONS=true). Seeders idempotentes.
if [ "$RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate --force
  # php artisan db:seed --force
  php artisan db:seed --class=SetupSeeder --force
fi

exec "$@"