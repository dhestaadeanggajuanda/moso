# Changelog

Semua perubahan penting proyek ini dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan penomoran versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Unreleased]

### Diperbaiki

- CI GitHub Actions memakai SQLite (`.env.example` menargetkan MySQL Docker lokal) dan menjalankan `composer audit:all`.
- Tes tidak lagi bergantung pada nilai `SECURITY_ENFORCE_2FA` di `.env`.

## [3.0.1] - 2026-10-17

Rilis pertama CMS Portal Berita Pemerintah berbasis Laravel 13 + Vue 3 (Fase 0 dan Fase 1).

### Ditambahkan

- Fondasi Laravel 13 dengan Vue Starter Kit resmi: Inertia 3, Vue 3 + TypeScript, Tailwind 4, shadcn-vue, Fortify, Wayfinder, dan Pest.
- Layanan development via Docker (`composer docker:up`): MySQL 8.4, RustFS (object storage S3), dan Mailpit, plus profil Garage sebagai alternatif.
- Disk `s3` (bucket publik baca-saja tanpa listing) dan `s3_private` (akses lewat `temporaryUrl`). Tidak memakai ACL per objek agar kompatibel dengan Garage.
- Model dan migrasi inti: `pages` (blok Page Builder dalam JSON), `categories` + pivot `article_category`, `articles` (dengan soft delete), `agendas`, dan `settings`.
- Panel admin di `/admin` untuk artikel, kategori, halaman, agenda, dan pengaturan situs. Antarmuka berbahasa Indonesia dan mobile-first.
- Role dan permission (spatie/laravel-permission): Super Admin, Approver, Editor, Kontributor. Seeder role, pengaturan bawaan, Super Admin, dan konten demo (khusus lokal).
- Laravel Boost beserta panduan proyek di `.ai/guidelines/` (arsitektur, aturan keamanan OWASP, aturan frontend dan tema).
- Dokumentasi: `docs/STORAGE-S3.md`, `docs/SECURITY-OWASP.md`, dan `docs/SSO-ROADMAP.md`.

### Keamanan

- Middleware `SecurityHeaders`: CSP berbasis nonce, HSTS di HTTPS, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, dan COOP.
- Registrasi publik dimatikan. Super Admin dan Approver wajib 2FA (`SECURITY_ENFORCE_2FA`).
- Otorisasi berbasis Policy di setiap aksi admin. Kontributor hanya bisa mengubah artikel miliknya selagi berstatus draf atau perlu revisi.
- Sanitasi HTML isi artikel di server (symfony/html-sanitizer).
- Unggahan gambar di-encode ulang ke WebP: metadata EXIF dibuang, nama file UUID, SVG ditolak.
- Nilai pengaturan rahasia disimpan terenkripsi dan tidak pernah dikirim ke browser. Session terenkripsi.
- Advisory npm tinypool (GHSA-5gmw-xhrv-c9v3) ditutup lewat `overrides`.

[Unreleased]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.0.1...HEAD
[3.0.1]: https://github.com/dhestaadeanggajuanda/moso/releases/tag/v3.0.1
