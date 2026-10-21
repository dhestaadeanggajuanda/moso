# Changelog

Semua perubahan penting proyek ini dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan penomoran versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Unreleased]

### Ditambahkan

- Halaman admin "Pengguna" (`/admin/users`, Super Admin) untuk mengelola akun pegawai, karena registrasi publik mati dan login SSO tidak pernah membuat akun:
    - Daftar akun dengan pencarian nama/email, saringan peran dan status, serta penanda tertaut SSO dan 2FA.
    - Tambah akun (nama, email, peran) dengan akses awal: tautan atur kata sandi lewat email (`AccountCreated`, berlaku sesuai `auth.passwords.users.expire`), kata sandi awal, atau tanpa kata sandi (masuk lewat SSO, hanya bila SSO aktif). Akun yang dibuat Super Admin langsung berstatus email terverifikasi.
    - Ubah nama, email, dan peran; nonaktifkan atau aktifkan kembali akun; lihat dan lepas tautan SSO.
    - Menu "Pengguna" di sidebar (`auth.can.users`).
- Kolom `users.deactivated_at`. Akun dinonaktifkan, tidak dihapus, sehingga artikel dan jejak audit tetap menunjuk ke penulisnya.

### Keamanan

- Akun nonaktif tidak bisa masuk lewat kata sandi, "ingat saya", passkey, maupun SSO, sesinya berakhir pada permintaan berikutnya, dan tautan reset kata sandi tidak dikirim (driver user provider `active-users` di `config/auth.php`). Login kata sandi tetap menjawab dengan pesan umum.
- `UserPolicy`: hanya Super Admin yang mengelola akun. Bila `users.manage` kelak diberikan ke role lain, pemegangnya tetap tidak bisa mengubah akun Super Admin atau memberi peran Super Admin. Super Admin tidak bisa mengubah perannya sendiri atau menonaktifkan akunnya sendiri.
- Log audit `user.created`, `user.updated` (hanya nama kolom), `user.deactivated`, `user.activated`, dan `sso.unlinked`, masing-masing dengan `actor_id`. Kata sandi tidak pernah dikirim ke browser, lewat email, atau ke log.

## [3.8.0] - 2026-10-20

Asisten penulis berita: draf berita atau liputan kegiatan disusun oleh layanan pusat moso-agentic dari judul, foto, dan deskripsi pendek. Fitur ini mati sampai `AGENTIC_ENABLED=true`.

### Ditambahkan

- Tombol "Tulis dengan AI" di form artikel (`resources/js/Admin/AiDraft/AiDraftDialog.vue`):
    - Memakai bahan dari form (judul, ringkasan, foto yang dipilih atau gambar utama yang tersimpan) ditambah info tambahan opsional, untuk jenis "Berita atau pengumuman" atau "Liputan kegiatan".
    - Draf disusun asinkron di agentic. Browser menanyakan status tiap 2 detik dan menampilkan tahapnya ("Membaca foto…", "Mengingat berita sebelumnya…", "Menulis draf…", "Memeriksa fakta…").
    - Pratinjau hasil beserta daftar "Perlu dikonfirmasi". "Pakai draf ini" mengisi isi artikel, juga ringkasan, teks alternatif gambar, dan kategori bila masih kosong; isian yang sudah ada hanya ditimpa setelah dikonfirmasi. "Tulis ulang" menerima instruksi perbaikan.
    - Route `admin.articles.ai-drafts.store` (10 per menit) dan `admin.articles.ai-drafts.show` (60 per menit, untuk polling), tabel `article_ai_drafts`. Draf yang dipakai ditautkan ke artikel saat disimpan.
- Halaman admin "Asisten AI" (`/admin/assistant`, Super Admin) dengan tab Profil dinas (nama resmi, tugas, pejabat, program, kontak), Gaya tulisan (aturan hasil belajar yang bisa diubah, dikunci, atau direset), dan Status (koneksi, berita terindeks, draf 30 hari).
- Memori berita terbit: artikel yang diterbitkan, diubah saat terbit, diarsipkan, atau dihapus disalin ke agentic lewat antrean (event `ArticlePublished`/`ArticleUnpublished`, listener `SyncArticleToAgentic`). Perintah `php artisan agentic:sync-articles {--since=}` mengisi memori dari berita lama.
- Konfigurasi `config/agentic.php` dengan variabel `AGENTIC_ENABLED`, `AGENTIC_URL`, dan `AGENTIC_TOKEN`. Klien `App\Services\Agentic\AgenticClient` hanya memakai Http facade, tanpa dependensi baru.

### Diubah

- Artikel yang isinya masih memuat penanda "[Perlu dikonfirmasi: …]" tidak bisa diajukan atau diterbitkan. Form artikel menampilkan peringatan selama penanda itu ada.
- Isi, ringkasan, teks alternatif, judul, dan kategori di form artikel kini terkontrol (v-model) dan tetap terkirim lewat `<Form>` seperti sebelumnya.

### Keamanan

- Permintaan ke agentic hanya ke `AGENTIC_URL`, tanpa mengikuti redirect, dengan token per dinas. Foto dikirim sebagai byte (dibaca dari disk `s3` bila tidak ada unggahan baru), tidak pernah sebagai URL. Kegagalan tidak pernah memutus halaman, dan log hanya mencatat operasi dan status tanpa isi konten.
- HTML draf disanitasi ulang dengan `HtmlSanitizer` sebelum dikirim ke browser. Draf hanya bisa dibaca pemiliknya (404 untuk pengguna lain), penulis dikirim ke agentic sebagai HMAC ID, dan draf hanya ditautkan ke artikel bila milik pengguna yang sama.
- `php artisan security:check` memeriksa token agentic dan memastikan `AGENTIC_URL` memakai HTTPS kecuali mengarah ke jaringan privat.

## [3.7.0] - 2026-10-19

Deploy dengan Docker: Laravel Octane di FrankenPHP (PHP 8.5) untuk server aplikasi, dan Keycloak di server terpisah.

### Ditambahkan

- Deploy dengan Docker (panduan `docs/DEPLOY.md`):
    - `Dockerfile` multi-stage: Laravel Octane di FrankenPHP dengan PHP 8.5 (berjalan sebagai `www-data`), dan image terpisah untuk server SSR Inertia. Aset dan bundle SSR dibangun di dalam image.
    - `docker/deploy/app/compose.yaml`: app, queue worker, scheduler, SSR, MySQL 8.4, dan Redis 8 untuk server aplikasi. RustFS dan Keycloak di server terpisah lewat `.env`. Port hanya terikat ke `127.0.0.1` di belakang reverse proxy HTTPS.
    - `docker/deploy/keycloak/compose.yaml`: Keycloak 26 mode produksi dengan MySQL untuk server SSO terpisah, realm `moso` diimpor otomatis.
    - Contoh `.env` untuk kedua server, `php.ini` produksi, dan entrypoint yang membuat cache konfigurasi saat start dan menjalankan migrasi di container `app`.
- CI menguji PHP 8.3 dan 8.5, dan memastikan image produksi tetap bisa dibangun.
- Dependensi baru: `laravel/octane`.

### Diubah

- Realm Keycloak (`docker/keycloak/moso-realm.json`) kini mengambil URL portal dari `MOSO_APP_URL`, sehingga sama untuk lokal, server development, dan produksi.
- Alamat server SSR Inertia bisa diatur lewat `INERTIA_SSR_URL`.

### Keamanan

- Di Octane, daftar permission spatie di-reset setiap request, agar peran yang dicabut tidak masih berlaku di worker lain.

## [3.6.0] - 2026-10-19

Login SSO OpenID Connect untuk pegawai dinas, berjalan berdampingan dengan login lokal selama masa transisi.

### Ditambahkan

- Login SSO OpenID Connect (lihat `docs/SSO-ROADMAP.md`):
    - Tombol "Masuk dengan SSO" di halaman login, route `/auth/sso/redirect` dan `/auth/sso/callback`. Authorization Code + PKCE S256, `state` dan `nonce` sekali pakai, ID token divalidasi penuh (tanda tangan JWKS, `iss`, `aud`/`azp`, `exp`/`iat`, `nonce`), kunci IdP diambil ulang saat dirotasi. Berlaku untuk IdP OIDC apa pun lewat discovery.
    - Tabel `sso_identities`: akun ditautkan lewat klaim `sub`. Login pertama menautkan akun yang sudah ada bila IdP menyatakan email terverifikasi; akun tidak pernah dibuat otomatis, dan akun yang sudah tertaut tidak bisa diambil alih subject lain.
    - MFA di IdP (klaim `amr`/`acr`, `SSO_MFA_AMR`/`SSO_MFA_ACR`) menggantikan 2FA aplikasi; tanpa bukti MFA, 2FA aplikasi yang aktif tetap diminta.
    - Logout mengakhiri sesi di IdP (RP-initiated logout).
    - `SSO_LOCAL_LOGIN=false` mematikan login password, passkey, dan reset kata sandi setelah semua pegawai memakai SSO.
    - Log audit `sso.linked`, `sso.login`, `sso.authenticated`, dan `sso.failed` (tanpa token). `php artisan security:check` ikut memeriksa issuer HTTPS dan kredensial klien.
    - Keycloak lokal untuk pengembangan (profil Docker `sso`, realm `docker/keycloak/moso-realm.json`).
    - Dependensi baru: `firebase/php-jwt`.

### Diperbaiki

- Log audit di lingkungan tes tidak lagi jatuh ke logger darurat (`LOG_AUDIT_STACK=null` dibaca sebagai channel `null`).

## [3.5.0] - 2026-10-19

Fase 8: audit dan hardening OWASP Top 10:2025 untuk seluruh kode, termasuk perlindungan tautan reset kata sandi, pembatasan reset, akhir sesi lintas perangkat, dan penguncian artikel terbit untuk Approver.

### Ditambahkan

- Fase 8, audit dan hardening OWASP Top 10:2025 (laporan lengkap di `docs/SECURITY-OWASP.md`, bagian "Audit Fase 8"):
    - Perintah `php artisan security:check` untuk memeriksa konfigurasi produksi (APP_DEBUG, HTTPS, cookie sesi, CSP, HSTS, 2FA, registrasi publik). Keluar dengan kode gagal bila ada pemeriksaan wajib yang tidak lolos, sehingga bisa dipakai di skrip deploy.
    - Variabel `TRUSTED_PROXIES` (`config/trustedproxy.php`) untuk reverse proxy atau load balancer yang mengakhiri TLS, dan `TRUSTED_HOSTS` untuk host tambahan selain host `APP_URL`.

### Diubah

- Artikel yang sudah terbit atau diarsipkan kini hanya bisa diubah dan dihapus oleh Approver dan Super Admin. Editor tetap bisa mengubah artikel sampai statusnya siap terbit; koreksi artikel terbit lewat Approver. Penulis artikel tidak bisa mengubah isi artikelnya sendiri sesudah terbit (prinsip empat mata).
- Pesan sesudah meminta tautan reset kata sandi kini sama untuk email terdaftar maupun tidak.

### Keamanan

- Tautan reset kata sandi tidak bisa lagi diarahkan ke domain penyerang lewat header `Host`: hanya host `APP_URL` dan `TRUSTED_HOSTS` yang dilayani (host lain dibalas 400). Health check load balancer ke `/up` harus mengirim host publik atau didaftarkan di `TRUSTED_HOSTS`.
- Permintaan tautan reset dan penyimpanan kata sandi baru dibatasi (3 per menit per email, 10 per menit per IP), dan jawabannya tidak lagi membocorkan email mana yang terdaftar.
- Sesi di perangkat lain berakhir saat kata sandi diganti atau direset.
- Klien Media Center tidak lagi mengikuti redirect, dan tautan berita dengan kredensial atau port lain ditolak.
- `HtmlSanitizer` membuang atribut `srcset`/`imagesrcset`; pola tautan aman menolak backslash (`/\evil`).
- Route bertanda tangan `storage/{path}` milik disk lokal yang tidak dipakai dimatikan.

## [3.4.0] - 2026-10-19

Fase 7: widget aksesibilitas WCAG 2.2 buatan sendiri yang bisa dipasang lintas aplikasi, terpasang di situs publik moso.

### Ditambahkan

- Fase 7, widget aksesibilitas `packages/a11y-widget` (`@moso/a11y-widget`, workspace npm):
    - Web Component `<a11y-widget>` dengan Shadow DOM, TypeScript tanpa framework: ukuran teks, spasi teks (WCAG 1.4.12), font mudah dibaca dan ramah disleksia (Atkinson Hyperlegible dan OpenDyslexic, di-self-host dan baru dimuat saat dipilih), sorot tautan, kontras tinggi, skala abu-abu, warna terbalik, sembunyikan gambar, hentikan animasi (WCAG 2.2.2), kursor besar, panduan baca, bacakan teks (Web Speech API, `id-ID`, suara lokal diutamakan), dan atur ulang. Preferensi disimpan di `localStorage`, tanpa cookie dan tanpa permintaan ke server.
    - Widget sendiri memenuhi AA: dialog berlabel, fokus pindah ke judul saat dibuka, `Esc` menutup dan mengembalikan fokus ke pemicu, `aria-pressed`, pengumuman `aria-live`, target minimal 44px, dan panel selebar layar di ponsel.
    - Build ESM dan IIFE (`npm run build:widget`, sekitar 7,5 KB gzip; font dan lisensinya sebagai berkas terpisah) untuk dipasang di aplikasi lain dengan satu tag `<script>` berkonfigurasi `data-*`. API `mount`, `unmount`, `define`, dan event `a11y-change`.
    - Terpasang di situs publik moso (hanya halaman `site/*`; dilepas dan tampilan dipulihkan saat pindah ke halaman lain). Warna aksen mengikuti token TemaModern lewat `--a11y-accent`. HeroSlider berhenti bergeser otomatis dan NewsCarousel menggulir tanpa animasi saat "Hentikan animasi" aktif.
    - Pengujian Vitest + happy-dom (`npm run test:widget`), ikut dijalankan `composer ci:check`.
    - `docs/ACCESSIBILITY.md` (target WCAG 2.2 AA, kewajiban tema, cara pasang dan menguji widget) dan README paket.

## [3.3.0] - 2026-10-18

Fase 6: blok HeroSlider dan NewsCarousel yang aksesibel untuk TemaModern, serta SOP frontend untuk tim magang.

### Ditambahkan

- Fase 6, pelengkap TemaModern dan SOP magang:
    - Blok HeroSlider (Banner Bergeser): satu sampai lima slide berfoto dengan judul, subjudul, dan tombol. Bergeser manual; bergeser otomatis hanya bila diaktifkan (jeda 5–15 detik), dengan tombol jeda, berhenti saat kursor atau fokus di dalam, dan tidak bergerak saat pengguna memilih gerak dikurangi. Pola karusel WAI-ARIA, slide tidak aktif dibuat inert, dan panah kiri/kanan bekerja di kontrol.
    - Blok NewsCarousel (Karusel Berita): berita lokal dan Media Center dalam deret yang digeser dengan tombol, sentuhan, atau Tab; tidak pernah bergeser otomatis.
    - Inspector Page Builder untuk kedua blok; unggah foto blok kini memakai composable bersama `useBlockImageUpload`.
    - `docs/SOP-FRONTEND.md` (batas wewenang tim magang, mobile-first, token desain, daftar periksa WCAG 2.2 AA, aturan karusel, keamanan template, alur kerja) dan `Themes/TemaModern/README.md`.

## [3.2.0] - 2026-10-18

Fase 5 (alur persetujuan artikel dan jejak audit) serta situs publik lanjutan: halaman PPID, E-SAKIP, indeks berita, dan unggah logo.

### Ditambahkan

- Fase 5, alur persetujuan artikel:
    - Status artikel kini berpindah lewat `ArticleWorkflow`: ajukan, setujui (siap terbit), minta revisi (catatan wajib), terbitkan (bisa dijadwalkan), arsipkan, dan terbitkan ulang. Status dibaca ulang dengan row lock agar dua reviewer tidak menimpa satu sama lain.
    - Tabel `article_comments` menyimpan riwayat setiap perubahan status beserta catatannya, dan komentar diskusi antara penulis dan reviewer.
    - Halaman tinjau artikel di admin (`/admin/articles/{id}`): pratinjau isi, tombol aksi (`ActionButtons.vue`) yang dihitung Policy di server, riwayat, dan kolom komentar. Daftar artikel menampilkan antrean "Menunggu review" dan "Siap terbit" bagi reviewer.
    - Peran: Kontributor mengajukan artikelnya, Editor mereview, Approver menerbitkan dan mengarsipkan.
    - Jejak audit di channel log `audit`: transisi status, login, gagal login, lockout, logout, reset password, aktif/nonaktif 2FA, serta pemberian dan pencabutan peran/permission.
- Situs publik lanjutan:
    - Halaman Informasi Publik `/ppid`: dokumen terbit per kategori (berkala, serta merta, setiap saat) dengan jumlah per kategori, penjelasan UU 14/2008, dan paginasi.
    - Halaman E-SAKIP `/sakip`: dokumen terbit dikelompokkan per tahun dan diurutkan menurut siklus SAKIP (Renstra sampai LKjIP), dengan saringan tahun dan jenis dokumen.
    - Indeks berita `/berita`: berita dinas terbit, terbaru lebih dulu, 12 per halaman, dengan saringan kategori. Blok NewsGridHybrid mendapat tautan "Lihat semua berita" (mengikuti kategori blok), dan detail berita menautkan kembali ke indeks.
    - Unggah logo Pemkot dan logo dinas di halaman Pengaturan admin (JPG, PNG, atau WebP, maks. 2 MB), beserta tombol hapus logo.
    - `site.links` kini memuat `news`, `ppid`, dan `sakip`. Partial tema baru: `PageHero`, `PageNav`, `FilterChips`, dan `DocumentRow`.
- Terjemahan Bahasa Indonesia untuk pesan validasi, autentikasi, reset kata sandi, dan paginasi (`lang/id`, dengan cadangan `lang/en`).

### Diubah

- Tautan bawaan blok AksesCepat kini PPID, E-SAKIP, dan Pengaduan. Tile "Cari layanan" (yang menduplikasi kolom cari di banner) dan "Hubungi kami" dihapus dari bawaan; blok yang sudah tersimpan tidak berubah.
- Kepala halaman dan paginasi daftar layanan memakai partial tema bersama (`PageHero`, `PageNav`), tanpa perubahan tampilan.

### Dihapus

- Hapus akun sendiri dari Pengaturan profil. Sebelumnya fitur ini memicu error 500 bila pengguna masih punya artikel; akun kini hanya dikelola Super Admin.

### Diperbaiki

- Label blok AksesCepat tidak lagi meluap keluar petak. Empat tautan atau lebih kini tampil dua kolom di ponsel dan tablet, lalu empat kolom mulai lg. Sebelumnya empat kolom di semua lebar, dengan ruang label sekitar 60px di layar 360px. Tiga tautan tetap satu baris, dengan ikon di atas label sampai md (sebelumnya sm) agar label tidak terdesak ke padding di 640px. Ikon dalam satu baris kini sejajar, dan kata yang lebih lebar dari petak dibungkus di dalam petak.

### Keamanan

- Prinsip empat mata: penulis tidak boleh menyetujui atau menerbitkan artikelnya sendiri. Setiap aksi alur dicek ArticlePolicy di server, endpoint aksi dan komentar dibatasi 30/menit, dan aksi yang tidak lagi sesuai status ditolak alih-alih menimpa.
- Jejak audit tidak memuat password atau token; gagal login hanya mencatat email dan IP.
- Halaman PPID dan E-SAKIP hanya menampilkan dokumen terbit dan hanya mengirim tautan route unduhan, tidak pernah path storage.
- Logo hanya bisa diisi lewat unggahan (`SecureUploadService`: re-encode WebP, nama UUID, SVG ditolak), dicek Policy pengaturan dan dibatasi 20/menit. Formulir pengaturan teks menolak key `site.logo_*`. Saat logo diganti, hanya berkas hasil unggahan yang dihapus.

## [3.1.0] - 2026-10-18

Fase 2 sampai 4: modul kepatuhan dan API publik, integrasi Media Center dan Page Builder, serta renderer publik dengan tema TemaModern.

### Ditambahkan

- Fase 4, renderer publik dan tema:
    - Pluggable Themes: plugin Vite `virtual:themes` hanya mengompilasi tema di `VITE_THEMES` (JS dan CSS), alias `@theme`, dan `ThemeResolver` (tema halaman → `theme.active` → tema bawaan).
    - `Core/PageRenderer.vue` merender blok dengan `<component :is>` dari tema aktif; `BlockBoundary` mengisolasi blok yang gagal.
    - Situs publik: beranda (`site.home_page`), halaman Page Builder `/{slug}`, detail berita `/berita/{slug}`, serta daftar dan detail layanan `/layanan` dan `/layanan/{slug}` dengan pencarian. Halaman publik dirender server (Inertia SSR).
    - TemaModern: arah visual portal pemda standar yang dipoles modern (acuan jakarta.go.id). Header navy dengan slot logo Pemkot dan dinas, menu bertingkat (desktop dan ponsel), footer kontak dan pengaduan, blok HeroBanner, LayananList, NewsGridHybrid (badge Berita Dinas / Media Center), AgendaKegiatan, RichText, serta tampilan detail berita dan layanan. Font Plus Jakarta Sans di-self-host.
    - Blok baru LayananList (daftar layanan dengan kolom cari) dan AksesCepat (tautan berikon ke layanan, pengaduan, PPID, E-SAKIP, dan halaman penting lain, diatur pengelola).
    - HeroBanner kini punya foto latar (unggah dari editor), teks alternatif, dan kolom Cari layanan.
    - Pengaturan situs baru: `contact.hours`, `contact.complaint_url` (bawaan SP4N-LAPOR!), `site.logo_pemkot`, `site.logo_dinas`.
    - `PRODUCT.md` (konteks produk untuk skill impeccable).
- Fase 3, integrasi Media Center dan Page Builder:
    - `MediaCenterApiService` (Http facade) untuk WordPress REST API Media Center Kota, dengan mode tag, kategori, dan pencarian.
    - Cache stale-while-revalidate, retry hanya untuk GET, dan perintah `mediacenter:warm` terjadwal tiap 10 menit.
    - `HybridNewsAggregator` menggabungkan artikel lokal dengan berita Media Center: diurutkan per tanggal, judul ganda dibuang, dan berita lokal tetap tampil bila Media Center gagal.
    - Mesin Page Builder: `BlockRegistry` (whitelist blok HeroBanner, NewsGridHybrid, AgendaKegiatan, RichText), validasi props per tipe blok, dan `BlockHydrator` yang menyuntikkan data saat render.
    - Editor drag-and-drop `/admin/pages/{id}/builder` (vuedraggable@next + Pinia): palet blok, kanvas, dan inspector. Tersedia juga tombol Tambah, Naik, dan Turun sebagai alternatif seret.
    - Blok NewsGridHybrid menyimpan `fetch_media_center` dan `media_center_keyword` di `pages.content_blocks`.
- Fase 2, modul kepatuhan:
    - Dokumen PPID (`public_documents`): kategori berkala, serta merta, dan setiap saat.
    - Layanan/Standar Pelayanan (`services`): persyaratan, prosedur, jangka waktu, biaya, produk, dan saluran pengaduan.
    - E-SAKIP (`e_sakip`): Renstra, Renja, IKU, PK, Rencana Aksi, dan LKjIP.
- Menu Builder bertingkat (`menus`, maksimal 3 tingkat) untuk header dan footer. Item dapat menautkan halaman internal atau URL.
- Halaman admin untuk keempat modul, dengan pratinjau berkas untuk pengelola.
- Unggahan PDF ke bucket privat lewat `SecureDocumentService`, dan unduhan publik `/unduh/ppid/{slug}` serta `/unduh/sakip/{id}` lewat URL bertanda tangan.
- API publik read-only `/api/v1`: `services`, `services/{slug}`, `ppid`, `sakip`, dan `menus/{location}`. Hanya data terbit, dengan rate limit 60/menit, `Cache-Control` + ETag, dan API Resource yang hanya berisi field yang diizinkan.
- Data contoh Fase 2 di `DemoContentSeeder` (khusus lokal).

### Keamanan

- Halaman, artikel, dan layanan yang belum terbit (termasuk artikel terjadwal) dibalas 404 di situs publik.
- Tautan dari pengaturan (pengaduan, media sosial) hanya dikirim ke tema bila skemanya aman. Path foto banner dan logo dibatasi ke pola hasil unggahan.
- Unggah foto Page Builder lewat `SecureUploadService`, dicek Policy halaman dan dibatasi 20/menit.
- Slug sistem (`layanan`, `berita`, `unduh`, dan lainnya) tidak bisa dipakai halaman, baik diisi manual maupun dibuat otomatis.
- Permintaan ke Media Center hanya menuju base URL dari config, dengan kata kunci `^[a-z0-9-]{1,50}$` (anti-SSRF). Tautan dan gambar hanya diterima dari host Media Center, dan teksnya diubah menjadi teks polos.
- `content_blocks` divalidasi terhadap `BlockRegistry`: tipe asing ditolak, props asing dibuang, HTML blok teks disanitasi, dan tautan tombol dibatasi ke URL aman.
- CSP `img-src` mengizinkan host gambar Media Center saja (bukan untuk script).
- Dokumen yang belum terbit dibalas 404 di jalur publik. Path storage tidak pernah dikirim ke API atau frontend.
- Unggahan PDF diperiksa lewat MIME dan tanda tangan `%PDF-`. Nama unduhan dibentuk dari slug, bukan dari nama berkas pengguna.
- URL menu dibatasi ke `/...` atau `http(s)://`. Penyusunan menu juga dicegah membentuk siklus induk-anak.
- Rate limit unduhan dokumen: 30/menit per IP.

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

[Unreleased]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.8.0...HEAD
[3.8.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.7.0...v3.8.0
[3.7.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.6.0...v3.7.0
[3.6.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.5.0...v3.6.0
[3.5.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.4.0...v3.5.0
[3.4.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.3.0...v3.4.0
[3.3.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.2.0...v3.3.0
[3.2.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.1.0...v3.2.0
[3.1.0]: https://github.com/dhestaadeanggajuanda/moso/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/dhestaadeanggajuanda/moso/releases/tag/v3.0.1
