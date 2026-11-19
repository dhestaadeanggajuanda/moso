#!/bin/bash
# Backup database satu website ke berkas .sql.gz, dijalankan sebagai user website itu.
#
#   backup-mysql.sh <slug>
#
# Dipakai oleh moso-backup.timer (harian) dan oleh podman-auto-update.service sebelum pembaruan.
# MOSO_BACKUP_DIR (bawaan ~/moso/backups) dan MOSO_BACKUP_KEEP_DAYS (bawaan 14) bisa diatur.
# Kata sandi root dibaca di dalam container dari environment-nya, tidak lewat baris perintah.
set -euo pipefail

slug=${1:?Pemakaian: backup-mysql.sh <slug>}
dir=${MOSO_BACKUP_DIR:-$HOME/moso/backups}
keep=${MOSO_BACKUP_KEEP_DAYS:-14}

umask 077
mkdir -p "$dir"

out="$dir/$slug-$(date +%Y%m%d-%H%M%S).sql.gz"
tmp="$out.tmp"
trap 'rm -f "$tmp"' EXIT

podman exec "$slug-mysql" sh -c \
    'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --events "$MYSQL_DATABASE"' \
    | gzip > "$tmp"

# Cadangan sah bila diakhiri baris "Dump completed" (mysqldump tidak menulisnya bila terputus).
if ! gzip -dc "$tmp" | tail -n 1 | grep -q 'Dump completed'; then
    echo "Backup $slug tidak lengkap, dibuang." >&2
    exit 1
fi

mv "$tmp" "$out"
find "$dir" -maxdepth 1 -name "$slug-*.sql.gz" -mtime "+$keep" -delete
echo "Backup tersimpan: $out"
