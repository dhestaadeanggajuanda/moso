#!/bin/sh
# Memasang (atau memperbarui) satu website moso sebagai container Podman rootless yang dikelola
# Quadlet (systemd user). Jalankan sebagai user Linux milik website itu, bukan root.
#
#   sh install-instance.sh <slug> <port-app> [berkas-env]
#
#   slug         nama website/dinas, huruf kecil, angka, tanda hubung (mis. dishub)
#   port-app     port 127.0.0.1 untuk reverse proxy host (unik per website, mis. 8002)
#   berkas-env   bawaan ~/moso/instance.env (salinan docker/deploy/app/.env.example yang diisi)
#
# Variabel opsional:
#   MOSO_APP_IMAGE   bawaan localhost/moso-app:latest
#   MOSO_SSR_IMAGE   bawaan localhost/moso-ssr:latest
#   MOSO_HOME        direktori home pengganti (untuk uji render)
#   MOSO_SKIP_SYSTEMD=1   hanya tulis berkas; tidak memanggil podman dan systemctl
#
# Aman dijalankan ulang: unit dan berkas env dibuat ulang dari instance.env, lalu layanan
# dimulai ulang. Panduan lengkap: docs/DEPLOY.md, bagian "Podman rootless dengan Quadlet".
set -eu

here=$(cd "$(dirname "$0")" && pwd)

if [ "$#" -lt 2 ]; then
    sed -n '2,19p' "$0" >&2
    exit 64
fi

slug=$1
port=$2
base=${MOSO_HOME:-$HOME}
data="$base/moso"
env_src=${3:-$data/instance.env}
app_image=${MOSO_APP_IMAGE:-localhost/moso-app:latest}
ssr_image=${MOSO_SSR_IMAGE:-localhost/moso-ssr:latest}
skip=${MOSO_SKIP_SYSTEMD:-0}

case "$slug" in
    '' | *[!a-z0-9-]*)
        echo "Slug hanya boleh huruf kecil, angka, dan tanda hubung: $slug" >&2
        exit 64
        ;;
esac

case "$port" in
    '' | *[!0-9]*)
        echo "Port harus angka: $port" >&2
        exit 64
        ;;
esac

if [ "$skip" != "1" ] && [ "$(id -u)" -eq 0 ]; then
    echo "Jalankan sebagai user website (mis. sudo machinectl shell $slug@), bukan root." >&2
    exit 77
fi

if [ ! -f "$env_src" ]; then
    echo "Tidak ada $env_src. Salin docker/deploy/app/.env.example ke sana lalu isi." >&2
    exit 66
fi

umask 077
mkdir -p "$data/env" "$data/scripts" "$data/backups" \
    "$base/.config/containers/systemd" "$base/.config/systemd/user"
chmod 700 "$data" "$data/env"

# 1. Normalkan .env: buang komentar dan tanda kutip, lalu perluas ${NAMA}. Podman membaca berkas env
#    secara harfiah (tanpa kutip, tanpa ekspansi), sedangkan .env aplikasi boleh memakainya.
normalized=$(mktemp)
trap 'rm -f "$normalized"' EXIT

awk -v q="'" '
function trim(s) { sub(/^[ \t]+/, "", s); sub(/[ \t\r]+$/, "", s); return s }
/^[ \t]*#/ || /^[ \t]*$/ { next }
{
    eq = index($0, "=")
    if (eq == 0) { next }
    key = trim(substr($0, 1, eq - 1))
    val = trim(substr($0, eq + 1))
    if (key !~ /^[A-Za-z_][A-Za-z0-9_]*$/) { next }
    n = length(val)
    if (n >= 2) {
        first = substr(val, 1, 1); last = substr(val, n, 1)
        if ((first == "\"" || first == q) && first == last) { val = substr(val, 2, n - 2) }
    }
    out = ""; rest = val
    while (match(rest, /\$\{[A-Za-z_][A-Za-z0-9_]*\}/)) {
        name = substr(rest, RSTART + 2, RLENGTH - 3)
        out = out substr(rest, 1, RSTART - 1) ((name in vars) ? vars[name] : "")
        rest = substr(rest, RSTART + RLENGTH)
    }
    val = out rest
    vars[key] = val
    print key "=" val
}' "$env_src" > "$normalized"

value() {
    # Nilai terakhir dari sebuah kunci di .env yang sudah dinormalkan (kosong bila tidak ada).
    grep "^$1=" "$normalized" | tail -n 1 | cut -d= -f2-
}

missing=
for key in APP_KEY APP_URL DB_DATABASE DB_USERNAME DB_PASSWORD DB_ROOT_PASSWORD REDIS_PASSWORD; do
    [ -n "$(value "$key")" ] || missing="$missing $key"
done

if [ -n "$missing" ]; then
    echo "Isi dulu di $env_src:$missing" >&2
    exit 65
fi

# Kata sandi database dan Redis masuk ke berkas env Podman secara harfiah. Tanda kutip, spasi,
# dan garis miring terbalik di dalamnya rawan dibaca berbeda, jadi tolak sekarang (pakai openssl rand -hex 24).
for key in DB_PASSWORD DB_ROOT_PASSWORD REDIS_PASSWORD; do
    case "$(value "$key")" in
        *[[:space:]\"\'\\]*)
            echo "$key tidak boleh memuat spasi, tanda kutip, atau garis miring terbalik. Pakai: openssl rand -hex 24" >&2
            exit 65
            ;;
    esac
done

case "$(value REDIS_CLIENT)" in
    phpredis | predis | '') ;;
    *)
        echo "REDIS_CLIENT harus phpredis (nama driver, bukan alamat host)." >&2
        exit 65
        ;;
esac

# 2. Berkas env per container (mode 0600). Container database memakai nama variabel image-nya.
#    Mode Podman memakai Redis dan MySQL milik website sendiri: variabel host dan user ACL dibuang.
{
    grep -vE '^(DB_HOST|DB_PORT|DB_ROOT_PASSWORD|REDIS_HOST|REDIS_PORT|REDIS_CLIENT|REDIS_USERNAME|HOST_[A-Z_]+|KEYCLOAK_[A-Z_]+)=' "$normalized"
    echo "DB_HOST=mysql"
    echo "DB_PORT=3306"
    echo "REDIS_HOST=redis"
    echo "REDIS_PORT=6379"
    echo "REDIS_CLIENT=phpredis"
    echo "INERTIA_SSR_URL=http://ssr:13714"
} > "$data/env/app.env"

{
    echo "MYSQL_ROOT_PASSWORD=$(value DB_ROOT_PASSWORD)"
    echo "MYSQL_DATABASE=$(value DB_DATABASE)"
    echo "MYSQL_USER=$(value DB_USERNAME)"
    echo "MYSQL_PASSWORD=$(value DB_PASSWORD)"
} > "$data/env/mysql.env"

{
    echo "REDIS_PASSWORD=$(value REDIS_PASSWORD)"
    echo "REDISCLI_AUTH=$(value REDIS_PASSWORD)"
} > "$data/env/redis.env"

chmod 600 "$data/env/"*.env

# 3. Skrip pendukung yang dipasang ke dalam container atau dijalankan timer.
for file in healthcheck-mysql.sh healthcheck-redis.sh redis-start.sh healthcheck-app.php backup-mysql.sh; do
    cp "$here/scripts/$file" "$data/scripts/$file"
    chmod 755 "$data/scripts/$file"
done

# 4. Render unit. Quadlet (.container .network .volume) dan unit systemd biasa (.service .timer).
render() {
    sed -e "s|@SLUG@|$slug|g" \
        -e "s|@APP_PORT@|$port|g" \
        -e "s|@APP_IMAGE@|$app_image|g" \
        -e "s|@SSR_IMAGE@|$ssr_image|g" "$1" > "$2"
    chmod 600 "$2"
}

for unit in "$here"/units/*; do
    name=$(basename "$unit")
    case "$name" in
        *.container | *.network | *.volume) render "$unit" "$base/.config/containers/systemd/$name" ;;
        *.service | *.timer) render "$unit" "$base/.config/systemd/user/$name" ;;
    esac
done

# 5. Pembaruan otomatis (hanya mysql dan redis yang berlabel AutoUpdate=registry): mingguan pada
#    dini hari, didahului backup. Backup gagal berarti pembaruan dibatalkan.
mkdir -p "$base/.config/systemd/user/podman-auto-update.timer.d" "$base/.config/systemd/user/podman-auto-update.service.d"

cat > "$base/.config/systemd/user/podman-auto-update.timer.d/10-moso.conf" <<'EOF'
[Timer]
OnCalendar=
OnCalendar=Sun *-*-* 03:30:00
RandomizedDelaySec=30m
EOF

cat > "$base/.config/systemd/user/podman-auto-update.service.d/10-moso-backup.conf" <<EOF
[Service]
ExecStartPre=%h/moso/scripts/backup-mysql.sh $slug
EOF

if [ "$skip" = "1" ]; then
    echo "Berkas ditulis di $base (MOSO_SKIP_SYSTEMD=1, systemd tidak disentuh)."
    exit 0
fi

# 6. Prasyarat runtime dan mulai layanan.
if ! command -v podman > /dev/null 2>&1; then
    echo "podman tidak ditemukan." >&2
    exit 69
fi

for image in "$app_image" "$ssr_image"; do
    if ! podman image exists "$image"; then
        echo "Image $image belum ada di penyimpanan user ini." >&2
        echo "Bangun atau muat dulu (lihat docs/DEPLOY.md), lalu jalankan ulang skrip ini." >&2
        exit 69
    fi
done

if [ "$(loginctl show-user "$(id -un)" -p Linger --value 2> /dev/null || echo no)" != "yes" ]; then
    echo "Peringatan: linger belum aktif, container berhenti saat user logout dan tidak naik saat reboot." >&2
    echo "Jalankan sebagai root: loginctl enable-linger $(id -un)" >&2
fi

systemctl --user daemon-reload
systemctl --user enable --now podman-auto-update.timer moso-backup.timer
# Unit Quadlet hasil generator sudah punya [Install]; app menarik mysql, redis, dan ssr.
systemctl --user restart moso-app.service moso-worker.service moso-scheduler.service

echo "Website $slug terpasang. Periksa: systemctl --user status moso-app moso-mysql moso-redis moso-ssr"
