#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY no está definida. Genérala con 'php artisan key:generate --show'." >&2
  exit 1
fi

php artisan package:discover --ansi
# borrar cache
php artisan optimize:clear

php artisan jwt:secret

# Solo un contenedor debe migrar (RUN_MIGRATIONS=true). Seeders idempotentes.
if [ "$RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate --force
  # php artisan db:seed --force
fi

exec "$@"