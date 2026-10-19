#!/bin/sh
# Entrypoint container app, worker, dan scheduler moso.
# Cache konfigurasi dibuat saat start karena variabel .env baru tersedia di runtime.
set -eu

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Hanya container `app` yang menjalankan migrasi (RUN_MIGRATIONS=true di compose).
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
