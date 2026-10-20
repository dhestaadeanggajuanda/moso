#!/bin/sh
# Jalankan perintah docker compose untuk satu instance moso (satu dinas) di server bersama.
#
#   docker/deploy/app/instance.sh <slug> <perintah compose...>
#
# Contoh:
#   docker/deploy/app/instance.sh dishub build
#   docker/deploy/app/instance.sh dishub up -d
#   docker/deploy/app/instance.sh dishub exec app php artisan db:seed --force
#   docker/deploy/app/instance.sh dishub logs -f app
#
# Setiap instance memakai:
#   - .env:     docker/deploy/app/instances/<slug>.env (salin dari .env.example)
#   - proyek:   moso-<slug> (container, jaringan, dan volume MySQL/Redis/log terpisah)
#   - override: docker/deploy/app/instances/<slug>.override.yaml bila ada (mis. extra_hosts)
# Image app dan SSR dipakai bersama semua instance (nama dinas diambil dari APP_NAME saat runtime).
set -eu

if [ "$#" -lt 2 ]; then
    echo "Pemakaian: $0 <slug> <perintah compose...>" >&2
    exit 64
fi

slug=$1
shift

case "$slug" in
    '' | *[!a-z0-9-]*)
        echo "Slug hanya boleh huruf kecil, angka, dan tanda hubung: $slug" >&2
        exit 64
        ;;
esac

dir=$(cd "$(dirname "$0")" && pwd)
env_file="$dir/instances/$slug.env"
override="$dir/instances/$slug.override.yaml"

if [ ! -f "$env_file" ]; then
    echo "Tidak ada $env_file. Salin dari $dir/.env.example lalu isi." >&2
    exit 66
fi

# compose.yaml membaca MOSO_ENV_FILE untuk env_file container app, worker, dan scheduler.
export MOSO_ENV_FILE="$env_file"

if [ -f "$override" ]; then
    exec docker compose -p "moso-$slug" --env-file "$env_file" -f "$dir/compose.yaml" -f "$override" "$@"
fi

exec docker compose -p "moso-$slug" --env-file "$env_file" -f "$dir/compose.yaml" "$@"
