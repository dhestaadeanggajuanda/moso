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

- Gunakan kredensial khusus aplikasi dengan izin minimal (hanya kedua bucket), bukan kredensial admin.
- Pasang TLS di depan endpoint S3 dan website, lalu ubah `AWS_ENDPOINT` dan `AWS_URL` ke `https://`.
- Batasi akses port S3 API (9000/3900) ke server aplikasi saja. Hanya endpoint baca publik yang diekspos ke internet.
- Aktifkan backup atau replikasi bucket sesuai kebijakan retensi arsip dinas.
