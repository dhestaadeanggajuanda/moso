# Moso: Arsitektur CMS Portal Berita Pemerintah

CMS portal berita untuk dinas di Kota Singkawang, dengan Pluggable Themes, Drag & Drop Page Builder, integrasi Media Center Kota, dan modul kepatuhan (PPID, Standar Pelayanan/Ombudsman, E-SAKIP).

## Konvensi umum

- Teks yang dilihat pengguna (UI, pesan validasi, notifikasi) memakai Bahasa Indonesia. Identifier kode (kelas, method, kolom, route name) memakai Bahasa Inggris.
- Zona waktu aplikasi `Asia/Pontianak` (WIB).
- Selalu cek `search-docs` (Laravel Boost) sebelum memakai fitur framework. Jangan menebak API Laravel 13, Inertia 3, Tailwind 4, atau Pest 4.

## Lokasi kode

| Area                                  | Lokasi                                                                                      |
| ------------------------------------- | ------------------------------------------------------------------------------------------- |
| Route panel admin                     | `routes/admin.php`: prefix `/admin`, nama `admin.*`, middleware `auth` + `verified`         |
| Route API publik read-only            | `routes/api.php`, prefix `/api/v1`                                                          |
| Controller admin / API / situs publik | `app/Http/Controllers/{Admin,Api/V1,Site}`                                                  |
| Enum status dan kategori              | `app/Enums` (string-backed, dengan method `label()` berbahasa Indonesia)                    |
| Logika domain                         | `app/Services/{Storage,Content,MediaCenter,News,PageBuilder,Workflow}`                      |
| Halaman Inertia admin                 | `resources/js/pages/admin/**` (memakai komponen shadcn-vue di `resources/js/components/ui`) |
| Page Builder (editor)                 | `resources/js/Builder/` + Pinia store `resources/js/stores/builder.ts`                      |
| Renderer publik                       | `resources/js/Core/PageRenderer.vue`                                                        |
| Tema                                  | `resources/js/Themes/{NamaTema}/{Layouts,Components,Partials}`                              |

## Page Builder

- `pages.content_blocks` adalah array JSON berisi blok: `{ "id": "uuid", "type": "NamaBlok", "props": { ... } }`.
- Tipe blok wajib terdaftar di `App\Services\PageBuilder\BlockRegistry`, beserta aturan validasi props-nya. Blok yang tidak terdaftar ditolak saat disimpan.
- Data dinamis (berita, agenda, layanan) **tidak** disimpan di JSON. Data itu disuntikkan saat render oleh `BlockHydrator` ke key `data`.
- Komponen tema untuk blok harus bernama sama dengan `type` dan berada di `Themes/{Tema}/Components/{type}.vue`.
- Blok `NewsGridHybrid` menggabungkan artikel lokal dengan Media Center bila `props.fetch_media_center = true`. Kata kunci diambil dari `props.media_center_keyword`.

## Storage

- Unggahan publik ke disk `s3`. File privat ke disk `s3_private`, diakses lewat `temporaryUrl()`.
- Jangan pernah memakai ACL per objek (`visibility` public / `setVisibility`), karena Garage tidak mendukungnya. Lihat `docs/STORAGE-S3.md`.
- Unggahan gambar wajib lewat `App\Services\Storage\SecureUploadService`.
- Dokumen PDF (PPID, E-SAKIP) wajib lewat `App\Services\Storage\SecureDocumentService`: selalu ke `s3_private`, lalu disajikan lewat route `documents.*` (publik, hanya bila terbit) atau aksi `file` di admin (dicek Policy). Jangan pernah mengirim path storage ke API atau frontend.

## Modul kepatuhan dan API publik

- PPID (`PublicDocument`, kategori `PublicDocumentCategory`), Standar Pelayanan (`Service`), E-SAKIP (`ESakip`, tabel `e_sakip`, kolom berkas `file_path_s3`), dan Menu (`Menu`, adjacency list, maks. 3 tingkat, tree via `Menu::treeFor()`).
- API publik read-only di `routes/api.php` (`/api/v1`, nama `api.v1.*`): hanya data terbit, middleware `throttle:api` + `cache.headers` (ETag), respons lewat API Resource di `app/Http/Resources` dengan field yang di-whitelist.

## Peran dan workflow artikel

- Role (spatie/laravel-permission): Super Admin, Approver, Editor, Kontributor. Super Admin melewati semua Gate lewat `Gate::before`.
- Status artikel (`App\Enums\ArticleStatus`): `draft` → `submitted` → `ready_to_publish` | `revision_required` → `published` (lalu `archived`).
- Transisi status hanya lewat `App\Services\Workflow\ArticleWorkflow`, dan setiap transisi dicatat.
