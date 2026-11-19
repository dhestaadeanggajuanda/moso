#!/bin/sh
# Health check container MySQL. `mysqladmin ping` berstatus 0 selama server hidup, tanpa perlu
# kata sandi (status tetap 0 walau akses ditolak), sehingga rahasia tidak lewat baris perintah.
exec mysqladmin ping -h 127.0.0.1 --silent
