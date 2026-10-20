# Object Storage S3 (RustFS / Garage)

Semua unggahan (gambar berita, dokumen PPID, laporan SAKIP) disimpan di object storage S3 self-hosted. Aplikasi hanya mengenal dua disk:

| Disk                                 | Bucket (`.env`)                       | Akses                                                                                                |
| ------------------------------------ | ------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `s3` (default, `FILESYSTEM_DISK=s3`) | `AWS_BUCKET` (`moso-public`)          | Objek bisa dibaca anonim lewat `Storage::disk('s3')->url($path)`. Isi bucket **tidak** bisa di-list. |
| `s3_private`                         | `AWS_PRIVATE_BUCKET` (`moso-private`) | Tertutup. Hanya lewat `Storage::disk('s3_private')->temporaryUrl($path, now()->addMinutes(10))`.     |

## Aturan

- **Jangan memakai ACL per objek** (`visibility => public`, `setVisibility()`). Garage tidak mendukung ACL. Akses publik diatur di level bucket.
- Simpan file draft atau sensitif di `s3_private`. Pindahkan ke `s3` hanya saat konten dipublikasikan.
- `AWS_REQUEST_CHECKSUM_CALCULATION` dan `AWS_RESPONSE_CHECKSUM_VALIDATION` bernilai `when_required`. AWS SDK versi baru mengirim checksum CRC secara default, dan sebagian server S3-compatible menolaknya.
- Path-style endpoint wajib (`AWS_USE_PATH_STYLE_ENDPOINT=true`).

## RustFS (default dev lokal)

```bash
composer docker:up
```

Perintah di atas menyalakan MySQL, RustFS, dan Mailpit. Service `storage-init` lalu membuat kedua bucket dan memasang policy baca-publik (hanya `s3:GetObject`) di `moso-public`. Lihat [docker/storage/init-buckets.sh](../docker/storage/init-buckets.sh).

|         | URL                                                                                             |
| ------- | ----------------------------------------------------------------------------------------------- |
| S3 API  | http://127.0.0.1:9100                                                                           |
| Console | http://127.0.0.1:9101 (login memakai `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` dari `.env`) |

`.env`:

```ini
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=http://127.0.0.1:9100
AWS_URL=http://127.0.0.1:9100/moso-public
```

## Garage (alternatif)

Garage memakai profil compose terpisah. Image-nya tidak punya shell, jadi semua perintah dijalankan lewat `/garage`.

```bash
docker compose -f docker/compose.yaml --env-file .env --profile garage up -d garage
```

Setup sekali jalan:

```bash
# 1. Lihat node ID
docker compose -f docker/compose.yaml --env-file .env exec garage /garage status

# 2. Tetapkan layout (ganti <NODE_ID> dengan 16 karakter awal node ID)
docker compose -f docker/compose.yaml --env-file .env exec garage /garage layout assign -z dc1 -c 10G <NODE_ID>
docker compose -f docker/compose.yaml --env-file .env exec garage /garage layout apply --version 1

# 3. Buat key, bucket, dan izin
docker compose -f docker/compose.yaml --env-file .env exec garage /garage key create moso-app
docker compose -f docker/compose.yaml --env-file .env exec garage /garage bucket create moso-public
docker compose -f docker/compose.yaml --env-file .env exec garage /garage bucket create moso-private
docker compose -f docker/compose.yaml --env-file .env exec garage /garage bucket allow --read --write --owner moso-public --key moso-app
docker compose -f docker/compose.yaml --env-file .env exec garage /garage bucket allow --read --write --owner moso-private --key moso-app

# 4. Akses baca publik untuk bucket publik (mode website)
docker compose -f docker/compose.yaml --env-file .env exec garage /garage bucket website --allow moso-public
```

`.env` untuk Garage. Key ID dan secret diambil dari output `key create`.

```ini
AWS_ACCESS_KEY_ID=GK...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=garage
AWS_ENDPOINT=http://127.0.0.1:3900
AWS_URL=http://moso-public.web.garage.localhost:3902
```

Endpoint website Garage memakai virtual-host (`<bucket>.web.<root_domain>`). Di produksi, arahkan domain CDN atau reverse proxy (misalnya `media.dinas.go.id`) ke port 3902 dan isi `AWS_URL` dengan domain tersebut.

## Produksi

Website dinas Pemkot Singkawang memakai satu server RustFS bersama. Setiap dinas punya bucket dan user sendiri, dibuat skrip provisioning di repo `moso-component` (lihat `docs/RUSTFS.md` di repo itu):

| Variabel `.env`                              | Nilai per dinas                                              |
| -------------------------------------------- | ------------------------------------------------------------ |
| `AWS_BUCKET`                                 | `<slug>-public` (baca anonim, tidak bisa di-list)            |
| `AWS_PRIVATE_BUCKET`                         | `<slug>-private`                                             |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | User `moso-<slug>`, hanya berhak atas kedua bucket miliknya  |
| `AWS_ENDPOINT`                               | `https://s3.<domain>` (lewat reverse proxy, bukan port 9000) |
| `AWS_URL`                                    | `https://s3.<domain>/<slug>-public`                          |

- Gunakan kredensial khusus aplikasi dengan izin minimal (hanya kedua bucket), bukan kredensial admin atau root.
- Pasang TLS di depan endpoint S3 dan website, lalu ubah `AWS_ENDPOINT` dan `AWS_URL` ke `https://`.
- **Endpoint S3 harus menerima GET/HEAD dari publik.** Gambar disajikan langsung dari `AWS_URL`, dan `App\Http\Controllers\Site\DocumentDownloadController` mengarahkan pengunduh dokumen PPID dan SAKIP ke presigned URL di `AWS_ENDPOINT`. Keduanya dibuka browser pengunjung, jadi membatasi seluruh port S3 ke server aplikasi membuat unduhan dokumen gagal.
- Aktifkan backup atau replikasi bucket sesuai kebijakan retensi arsip dinas.

Aturan di reverse proxy depan S3 API (RustFS 9000, Garage 3900):

| Aturan                                                                   | Alasan                                                                                                  |
| ------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------- |
| GET/HEAD dari mana saja                                                  | Pengunjung membuka gambar dan presigned URL. RustFS tetap memeriksa policy bucket dan tanda tangan URL. |
| PUT/POST/DELETE hanya dari IP server aplikasi                            | Hanya aplikasi yang mengunggah dan menghapus objek.                                                     |
| Header `Host` diteruskan apa adanya (`proxy_set_header Host $http_host`) | Host adalah bagian dari tanda tangan SigV4. Bila diubah, unggahan dan presigned URL ditolak.            |
| Console RustFS (9001) tidak dibuka ke publik                             | Hanya untuk admin, lewat tunnel SSH.                                                                    |

Contoh Nginx lengkap (termasuk cache objek publik): `rustfs/proxy/nginx-s3.conf` di repo `moso-component`. Server aplikasi juga harus menjangkau `AWS_ENDPOINT` lewat proxy yang sama (split DNS atau hairpin NAT), karena endpoint itu dipakai untuk unggah sekaligus untuk membuat presigned URL.
