#!/bin/sh
# Menjalankan Redis dengan kata sandi dari environment (REDIS_PASSWORD di redis.env), sehingga
# kata sandi tidak muncul di daftar proses host maupun berkas unit.
set -eu

exec redis-server --appendonly yes --requirepass "$REDIS_PASSWORD"
