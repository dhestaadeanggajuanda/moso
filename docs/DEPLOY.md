# Deploy dengan Docker

Panduan memasang moso di server development dan produksi. Susunan yang dipakai:

| Server          | Isi                                                                                                     | Berkas                             |
| --------------- | ------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| Server aplikasi | Laravel Octane di FrankenPHP (PHP 8.5), queue worker, scheduler, server SSR Inertia, MySQL 8.4, Redis 8 | `Dockerfile`, `docker/deploy/app/` |
| Server RustFS   | Object storage S3 (bucket publik dan privat)                                                            | `docs/STORAGE-S3.md`               |
| Server Keycloak | Keycloak 26 (mode produksi) dan MySQL 8.4 khusus Keycloak                                               | `docker/deploy/keycloak/`          |

HTTPS diakhiri reverse proxy yang sudah ada di masing-masing server (Nginx, Traefik, atau load balancer). Compose hanya membuka port HTTP di `127.0.0.1`, jadi container tidak bisa diakses langsung dari luar. MySQL, Redis, dan SSR tidak membuka port sama sekali.

## 1. Prasyarat

- Docker Engine 24+ dengan Docker Compose v2 di setiap server, dan salinan repo (`git clone`) di server aplikasi dan server Keycloak.
- Domain dan sertifikat TLS untuk portal (mis. `portal.dinas.example.go.id`), Keycloak (`sso.dinas.example.go.id`), dan RustFS (`s3.dinas.example.go.id`).
- Server aplikasi bisa menjangkau RustFS, Keycloak (lewat URL publik yang sama dengan browser), dan `mediacenter.singkawangkota.go.id` lewat HTTPS.

## 2. Server Keycloak

1. Siapkan `.env`:

    ```bash
    cp docker/deploy/keycloak/.env.example docker/deploy/keycloak/.env
    ```

    Isi `KC_HOSTNAME` (URL publik HTTPS), kata sandi admin dan database, `MOSO_APP_URL` (URL portal, tanpa garis miring di akhir), dan `MOSO_SSO_CLIENT_SECRET` (`openssl rand -hex 32`). Secret yang sama nanti dipakai di `.env` server aplikasi.

2. Jalankan:

    ```bash
    docker compose -f docker/deploy/keycloak/compose.yaml up -d
    ```

    Start pertama butuh sekitar satu menit. Realm `moso` dan klien `moso-portal` diimpor otomatis dari `docker/keycloak/moso-realm.json`: Standard Flow saja, PKCE S256 wajib, redirect URI persis `MOSO_APP_URL/auth/sso/callback`. Impor hanya terjadi sekali; perubahan berikutnya lewat console admin.

3. Arahkan reverse proxy HTTPS ke `127.0.0.1:8080` dan teruskan header `Host`, `X-Forwarded-For`, `X-Forwarded-Proto`, dan `X-Forwarded-Host`. Contoh Nginx:

    ```nginx
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
    }
    ```

4. Login ke console admin (`KC_HOSTNAME/admin`), aktifkan OTP untuk akun admin, lalu buat pengguna pegawai di realm `moso` dengan email yang sama seperti akun portalnya dan centang "Email verified".
5. Agar MFA di Keycloak menggantikan 2FA aplikasi, wajibkan OTP di realm dan tambahkan mapper "Authentication Method Reference (AMR)" ke client scope klien portal (lihat `docs/SSO-ROADMAP.md`, bagian MFA).

## 3. Server RustFS

Ikuti `docs/STORAGE-S3.md`: bucket `moso-public` (baca anonim) dan `moso-private`, akses lewat HTTPS. Catat endpoint, access key, dan secret key untuk `.env` server aplikasi.

## 4. Server aplikasi

1. Siapkan `.env`:

    ```bash
    cp docker/deploy/app/.env.example docker/deploy/app/.env
    ```

    Isi `APP_URL`, kata sandi MySQL dan Redis, kredensial RustFS, SMTP, dan blok SSO (`SSO_ISSUER=KC_HOSTNAME/realms/moso`, `SSO_CLIENT_SECRET` sama dengan `MOSO_SSO_CLIENT_SECRET`).

2. Bangun image lalu buat `APP_KEY`:

    ```bash
    docker compose -f docker/deploy/app/compose.yaml build
    ```

    ```bash
    docker compose -f docker/deploy/app/compose.yaml run --rm --no-deps --entrypoint php app artisan key:generate --show
    ```

    Salin hasilnya ke `APP_KEY` di `.env`.

3. Jalankan semua container. Container `app` menjalankan migrasi saat start:

    ```bash
    docker compose -f docker/deploy/app/compose.yaml up -d
    ```

4. Isi role, permission, pengaturan awal, dan akun Super Admin (dari `SEED_SUPERADMIN_*`; bila kata sandi kosong, seeder membuat kata sandi acak dan menampilkannya sekali). Kosongkan lagi `SEED_SUPERADMIN_*` sesudahnya.

    ```bash
    docker compose -f docker/deploy/app/compose.yaml exec app php artisan db:seed --force
    ```

5. Arahkan reverse proxy HTTPS ke `127.0.0.1:8000` dengan header yang sama seperti Keycloak, dan izinkan unggahan sampai 25 MB (`client_max_body_size 25m;` di Nginx). Header `Host` wajib diteruskan apa adanya: host selain `APP_URL` dan `TRUSTED_HOSTS` dibalas 400.
6. Isi `TRUSTED_PROXIES` dengan alamat asal request dari proxy. Bila proxy di host yang sama, itu gateway jaringan Docker (`docker network inspect moso-app_default`, atau subnet `172.16.0.0/12`). Tanpa ini, HTTPS dan IP pengunjung tidak terbaca benar.
7. Periksa konfigurasi:

    ```bash
    docker compose -f docker/deploy/app/compose.yaml exec app php artisan security:check
    ```

Health check load balancer ke `/up` harus mengirim header `Host` publik (atau host-nya didaftarkan di `TRUSTED_HOSTS`).

## 5. Server development

Untuk server development, pakai susunan yang sama dengan perbedaan berikut:

- `APP_ENV=staging`. TrustHosts tetap aktif, dan issuer SSO `http://` diizinkan (tetap disarankan HTTPS). `security:check` akan menandai "APP_ENV production" gagal; itu wajar di staging.
- Pertahankan `SSO_LOCAL_LOGIN=true` selama uji coba SSO, agar login password tetap bisa dipakai bila SSO bermasalah.
- Skenario uji SSO ada di `docs/SSO-ROADMAP.md`.

## 6. Beberapa dinas di satu server

Satu server aplikasi bisa menjalankan beberapa instance moso, satu per dinas. Setiap instance punya proyek compose `moso-<slug>` sendiri: container, jaringan, MySQL, Redis, dan log terpisah. Image app dan SSR dibangun sekali dan dipakai bersama; nama dinas di judul halaman diambil dari `APP_NAME` masing-masing saat runtime.

```
pengunjung --HTTPS--> reverse proxy pusat --HTTP:80--> Nginx di host --> 127.0.0.1:8001 (instance moso)
                                                                     --> 127.0.0.1:8002 (instance dishub)
```

1. Siapkan `.env` per instance di `docker/deploy/app/instances/` (diabaikan git):

    ```bash
    mkdir -p docker/deploy/app/instances
    cp docker/deploy/app/.env.example docker/deploy/app/instances/dishub.env
    ```

    Selain isian di bagian 4, setiap instance butuh:
    - `APP_URL` domainnya sendiri dan `APP_HTTP_PORT` yang berbeda (mis. 8001, 8002);
    - blok `AWS_*` dari `rustfs-secrets.env` dan blok `SSO_*` dari `client-secrets.env` (skrip provisioning `moso-component`), dengan `SSO_PROVIDER=pemkot`;
    - `TRUSTED_PROXIES` berisi rentang jaringan Docker dan IP reverse proxy pusat: `172.17.0.0/16,172.18.0.0/15,172.20.0.0/14,172.24.0.0/13,<IP proxy pusat>`. Rentang itu sengaja tidak mencakup `172.16.0.0/16`, agar perangkat lain di LAN tidak ikut dipercaya.

2. Semua perintah compose lewat `instance.sh <slug>`, yang memilih proyek, `.env`, dan override instance itu:

    ```bash
    sh docker/deploy/app/instance.sh dishub build
    sh docker/deploy/app/instance.sh dishub run --rm --no-deps --entrypoint php app artisan key:generate --show
    sh docker/deploy/app/instance.sh dishub up -d
    sh docker/deploy/app/instance.sh dishub exec app php artisan db:seed --force
    ```

    `build` cukup sekali untuk semua instance (image `moso-app:latest` dan `moso-ssr:latest`). Tema yang dikompilasi (`VITE_THEMES`) ikut image; dinas dengan tema berbeda butuh image sendiri lewat `MOSO_APP_IMAGE` dan `MOSO_SSR_IMAGE` di `.env`-nya.

3. Pasang Nginx di host sebagai penerus dari proxy pusat. Contoh lengkap: `docker/deploy/app/nginx-host.conf` (satu blok `server` per domain, host lain ditolak). Di AlmaLinux 9:

    ```bash
    dnf -y install nginx
    cp docker/deploy/app/nginx-host.conf /etc/nginx/conf.d/moso.conf
    setsebool -P httpd_can_network_connect 1
    nginx -t && systemctl enable --now nginx
    ```

    `httpd_can_network_connect` mengizinkan Nginx (SELinux) meneruskan ke port container. Buka port 80 hanya untuk proxy pusat:

    ```bash
    firewall-cmd --permanent --add-rich-rule='rule family=ipv4 source address=<IP proxy pusat>/32 port port=80 protocol=tcp accept'
    firewall-cmd --reload
    ```

4. Di reverse proxy pusat, arahkan setiap domain ke `http://<IP server aplikasi>:80`, dengan header `Host` asli dan `X-Forwarded-For`, `X-Forwarded-Proto: https`.
5. Server aplikasi harus menjangkau `https://sso.<domain>` dan `https://s3.<domain>` lewat proxy pusat (split DNS atau hairpin NAT), karena URL yang sama dipakai browser. Container tidak membaca `/etc/hosts` host. Bila DNS internal belum ada, buat `docker/deploy/app/instances/<slug>.override.yaml`:

    ```yaml
    x-hosts: &hosts
        extra_hosts:
            - 'sso.singkawangkota.go.id:<IP proxy pusat>'
            - 's3.singkawangkota.go.id:<IP proxy pusat>'

    services:
        app: *hosts
        worker: *hosts
        scheduler: *hosts
    ```

    Periksa dari dalam container:

    ```bash
    sh docker/deploy/app/instance.sh dishub exec app php -r 'echo file_get_contents("https://sso.singkawangkota.go.id/realms/pemkot/.well-known/openid-configuration") ? "SSO OK\n" : "SSO gagal\n";'
    ```

Setiap instance memakai MySQL dan Redis sendiri. Siapkan sekitar 1,5 sampai 2 GB RAM per instance.

## 7. Container yang berjalan

| Service     | Tugas                                                                                                        |
| ----------- | ------------------------------------------------------------------------------------------------------------ |
| `app`       | Octane + FrankenPHP di port 8000, `--max-requests=500` (worker didaur ulang untuk mencegah kebocoran memori) |
| `worker`    | `queue:work` untuk antrean Redis                                                                             |
| `scheduler` | `schedule:work`, termasuk `mediacenter:warm` tiap 10 menit                                                   |
| `ssr`       | Render halaman publik untuk SEO (`node bootstrap/ssr/app.js`), hanya dipanggil `app`                         |
| `mysql`     | Database aplikasi (volume `mysql-data`)                                                                      |
| `redis`     | Sesi, cache, dan antrean (volume `redis-data`, AOF aktif)                                                    |

Container `app`, `worker`, dan `scheduler` memakai image yang sama dan berjalan sebagai `www-data`. Saat start, entrypoint membuat cache konfigurasi, route, view, dan event dari `.env`.

## 8. Memperbarui versi

```bash
git pull
```

```bash
docker compose -f docker/deploy/app/compose.yaml up -d --build
```

Untuk beberapa instance: `git pull`, sekali `sh docker/deploy/app/instance.sh <slug> build`, lalu `sh docker/deploy/app/instance.sh <slug> up -d` untuk setiap instance.

Image baru dibangun, migrasi berjalan saat `app` start, dan worker Octane memuat kode baru karena container dibuat ulang. Setelah mengubah `.env`, cukup `up -d` (tanpa `--build`) agar container dibuat ulang dengan nilai baru.

Untuk rollback, beri tag image sebelum memperbarui (`MOSO_APP_IMAGE` dan `MOSO_SSR_IMAGE` di `.env`), lalu kembalikan tag-nya. Migrasi yang sudah berjalan tidak ikut dibatalkan.

## 9. Cadangan dan log

- Database aplikasi:

    ```bash
    docker compose -f docker/deploy/app/compose.yaml exec mysql sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction moso' > moso-$(date +%F).sql
    ```

- Database Keycloak: perintah yang sama di server Keycloak (`docker/deploy/keycloak/compose.yaml`, database `keycloak`).
- Log audit keamanan (`audit-*.log`, disimpan 365 hari) ada di volume `storage-logs`. Log aplikasi lain ke `docker compose logs`.
- Berkas unggahan ada di RustFS; cadangkan di server RustFS.

## 10. Catatan keamanan

- Jangan pernah commit `docker/deploy/*/.env`. Keduanya sudah diabaikan git.
- Port `app` (8000) dan Keycloak (8080) hanya terikat ke `127.0.0.1`; akses publik hanya lewat reverse proxy HTTPS.
- Server SSR punya endpoint render tanpa autentikasi; jangan membuka port 13714 ke luar jaringan Docker.
- Aktifkan OTP untuk akun admin Keycloak dan simpan `KC_ADMIN_PASSWORD` di pengelola rahasia.
