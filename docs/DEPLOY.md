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

`docker/deploy/keycloak` menjalankan satu node Keycloak dengan MySQL di VM yang sama. Susunan ini cocok untuk development atau staging satu dinas. Produksi bersama untuk 40 website dinas memakai cluster di repo `moso-component` (`docs/KEYCLOAK-CLUSTER.md`):

|                   | `docker/deploy/keycloak`                      | Cluster `moso-component`                                                                           |
| ----------------- | --------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Pemakaian         | Development atau staging satu dinas           | Produksi bersama 40 dinas                                                                          |
| Susunan           | 1 node Keycloak + MySQL 8.4 di VM yang sama   | PostgreSQL 17 di VM terpisah + 2 node Keycloak 26.8 di belakang reverse proxy                      |
| Realm             | `moso`                                        | `pemkot`                                                                                           |
| Klien             | `moso-portal`, diimpor dari `moso-realm.json` | `moso-<slug>` per dinas, dibuat `keycloak/scripts/create-clients.py`                               |
| Bukti MFA (`amr`) | Diatur manual (langkah 5)                     | Dipasang skrip: mapper AMR dan referensi AMR di flow browser, sehingga `amr` berisi `otp`          |
| `.env` aplikasi   | `SSO_ISSUER=KC_HOSTNAME/realms/moso`          | Blok dari `client-secrets.env` skrip (`SSO_ISSUER=.../realms/pemkot`, `SSO_CLIENT_ID=moso-<slug>`) |

Bila memakai cluster, ikuti dokumen di repo itu sebagai ganti langkah di bawah, lalu lanjut ke bagian 4 dengan `SSO_PROVIDER=pemkot`.

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
5. Agar MFA di Keycloak menggantikan 2FA aplikasi, wajibkan OTP di realm, tambahkan mapper "Authentication Method Reference (AMR)" ke client scope klien portal, dan isi nilai referensi AMR di langkah login flow browser (lihat `docs/SSO-ROADMAP.md`, bagian MFA).

## 3. Server RustFS

Ikuti `docs/STORAGE-S3.md`: bucket `moso-public` (baca anonim) dan `moso-private`, akses lewat HTTPS. Produksi bersama memakai bucket `<slug>-public` dan `<slug>-private` serta user `moso-<slug>` per dinas (bagian Produksi). Catat endpoint, access key, dan secret key untuk `.env` server aplikasi.

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

### MySQL dan Redis di host

Bawaannya setiap instance menjalankan container MySQL dan Redis sendiri. Itu tetap pilihan yang disarankan: data dinas terpisah penuh, dan cadangan serta pembaruan ikut proyek compose. Bila server sudah punya MySQL dan Redis di host (mis. untuk menghemat RAM), instance bisa memakainya lewat override `docker/deploy/app/host-services.override.example.yaml`. Satu instance bisa memakai host, instance lain tetap container.

1. MySQL di host: satu database dan satu user per dinas, hanya dari jaringan Docker.

    ```sql
    CREATE DATABASE moso_dishub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'moso_dishub'@'172.16.0.0/255.240.0.0' IDENTIFIED BY '<openssl rand -hex 24>';
    GRANT ALL ON moso_dishub.* TO 'moso_dishub'@'172.16.0.0/255.240.0.0';
    ```

    Di `/etc/my.cnf.d/`, tambahkan IP bridge Docker ke `bind-address` (mis. `bind-address = 127.0.0.1,172.17.0.1`) lalu restart MySQL.

2. Redis di host: matikan user `default` dan buat satu user ACL per dinas yang hanya boleh menyentuh key berawalan slug-nya. Di `/etc/redis/users.acl` (aktifkan dengan `aclfile /etc/redis/users.acl` di `redis.conf`):

    ```
    user default off
    user moso_dishub on ><kata sandi acak> ~moso_dishub_* &* +@all -@dangerous
    ```

    Tambahkan IP bridge Docker ke `bind` (mis. `bind 127.0.0.1 172.17.0.1`) lalu restart Redis. User satu dinas tidak bisa membaca atau menulis key dinas lain.

3. Izinkan jaringan Docker ke port 3306 dan 6379 tanpa membukanya ke publik:

    ```bash
    firewall-cmd --permanent --zone=trusted --add-source=172.16.0.0/12 && firewall-cmd --reload
    ```

4. Isi `instances/<slug>.env`:

    | Variabel                                    | Nilai                                                               |
    | ------------------------------------------- | ------------------------------------------------------------------- |
    | `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | database dan user MySQL host untuk dinas ini                        |
    | `DB_ROOT_PASSWORD`                          | tidak dipakai, tetapi wajib diisi nilai acak (dicek `compose.yaml`) |
    | `REDIS_USERNAME`, `REDIS_PASSWORD`          | user ACL Redis dinas ini                                            |
    | `REDIS_PREFIX`                              | `moso_<slug>_`, sesuai pola key di ACL                              |
    | `HOST_DB_PORT`, `HOST_REDIS_PORT`           | hanya bila port host bukan 3306 dan 6379                            |

5. Pasang override lalu jalankan seperti biasa. Container `mysql` dan `redis` instance itu tidak dijalankan, dan `app`, `worker`, serta `scheduler` terhubung ke host lewat `host.docker.internal`. Butuh Docker Compose v2.24 atau lebih baru (`docker compose version`).

    ```bash
    cp docker/deploy/app/host-services.override.example.yaml docker/deploy/app/instances/dishub.override.yaml
    ```

    ```bash
    sh docker/deploy/app/instance.sh dishub up -d
    ```

    Bila instance juga butuh `extra_hosts` untuk SSO dan S3 (langkah 5 di atas), gabungkan keduanya di berkas override yang sama.

Dengan susunan ini, cadangan database dijalankan di host (`mysqldump moso_dishub`), dan beban satu dinas di MySQL atau Redis ikut terasa oleh dinas lain.

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

Bila CHANGELOG menyebut perubahan peran atau permission (mis. 3.8.0: peran Penulis, Editor mengelola menu), jalankan sekali per instance sesudah `up -d`:

```bash
sh docker/deploy/app/instance.sh <slug> exec app php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

Seeder ini hanya menyelaraskan role dan permission bawaan; akun, peran yang sudah diberikan, dan konten tidak berubah.

Image baru dibangun, migrasi berjalan saat `app` start, dan worker Octane memuat kode baru karena container dibuat ulang. Setelah mengubah `.env`, cukup `up -d` (tanpa `--build`) agar container dibuat ulang dengan nilai baru.

Untuk rollback, beri tag image sebelum memperbarui (`MOSO_APP_IMAGE` dan `MOSO_SSR_IMAGE` di `.env`), lalu kembalikan tag-nya. Migrasi yang sudah berjalan tidak ikut dibatalkan.

### Apa yang berubah dan tidak berubah saat memperbarui

Kode (`git pull`) dan isi website (database tiap instance) terpisah:

| Disimpan di                       | Contoh                                                                                                       | Saat `git pull` + build |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------ | ----------------------- |
| Database instance                 | Susunan blok Page Builder (mis. moso memakai Banner Bergeser, dishub tidak), artikel, menu, pengaturan, logo | **Tidak berubah**       |
| RustFS                            | Foto dan dokumen PDF                                                                                         | **Tidak berubah**       |
| Kode tema (`resources/js/Themes`) | Bentuk visual tiap jenis blok, header, footer, warna                                                         | Ikut berubah            |

Jadi pembaruan keamanan tidak mengubah susunan halaman yang dibuat admin. Yang bisa berubah hanya tampilan sebuah jenis blok bila kode temanya diubah, dan perubahan itu berlaku untuk semua instance yang memakai tema itu. Cek CHANGELOG sebelum memperbarui; perubahan visual dicatat di sana.

`db:seed` juga aman dijalankan ulang: `DefaultHomePageSeeder` hanya membuat beranda dan menu dasar bila belum ada, dan tidak pernah menimpa susunan admin.

## 8a. Tampilan awal dan tema

**Tampilan awal.** `db:seed` di instance baru membuat halaman `beranda` yang langsung terbit (Banner, Akses Cepat, Layanan Publik, Berita Terkini, Agenda Kegiatan) dan menu header dasar (Beranda, Berita, Layanan, Informasi Publik, E-SAKIP), tanpa berita atau akun contoh. Admin tinggal mengganti teks dan foto banner di Page Builder, dan menyalakan berita Media Center dengan mengisi kata kunci dinas di blok Berita Terkini. Konten demo lengkap hanya dimuat di `APP_ENV=local`.

**Tema baru.** Tema ada di `resources/js/Themes/<NamaTema>` dan ikut dikompilasi ke image saat build, hanya bila tercantum di `VITE_THEMES`:

1. Tambahkan folder tema di repo (lihat `docs/SOP-FRONTEND.md`), commit, lalu `git pull` di server.
2. Pilih cara memakainya:
    - **Satu image untuk semua dinas.** Di `.env` instance yang membangun image, isi `VITE_THEMES=TemaModern,TemaBaru`, lalu build sekali. Setiap dinas memilih temanya sendiri (Pengaturan, atau kolom Tema per halaman); dinas yang tidak memilih tetap memakai tema pertama.
    - **Image terpisah per tema.** Bila hanya satu dinas yang memakai tema baru, beri nama image berbeda di `.env` dinas itu, mis. `MOSO_APP_IMAGE=moso-app:tema-baru`, `MOSO_SSR_IMAGE=moso-ssr:tema-baru`, dan `VITE_THEMES=TemaBaru`. Lalu `instance.sh <slug> build` dan `up -d` untuk dinas itu saja. Dinas lain tidak terpengaruh.
3. Halaman yang sudah ada tetap memakai susunan blok yang sama; hanya bentuk visualnya yang mengikuti tema baru. Tema wajib menyediakan komponen untuk setiap jenis blok yang dipakai, agar tidak ada blok yang kosong.

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
