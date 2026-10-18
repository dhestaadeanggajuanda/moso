# SOP Frontend Tema Publik (untuk Tim Magang)

Panduan kerja harian untuk mengubah tampilan situs publik moso. Berlaku untuk semua tema di `resources/js/Themes/`, dengan contoh dari tema bawaan **TemaModern**.

Dokumen terkait:

- `DESIGN.md`: sistem desain TemaModern (warna, tipografi, komponen, aturan).
- `PRODUCT.md`: siapa pengguna situs dan apa tujuannya.
- `docs/SECURITY-OWASP.md`: aturan keamanan proyek.

## 1. Batas wewenang

Tim magang **boleh** mengubah:

- Isi `<template>` dan class Tailwind di `Themes/{Tema}/Components`, `Views`, `Partials`, dan `Layouts`.
- Teks tampilan (label, kalimat ajakan) selama tetap Bahasa Indonesia baku yang ramah.

Tim magang **tidak boleh** mengubah tanpa review pengembang:

- Blok `<script setup>` di file tema. Props di sana adalah kontrak dengan server (BlockRegistry, BlockHydrator, controller). Setiap file tema diawali komentar peringatan yang sama.
- Nilai token di `Themes/{Tema}/theme.css` (warna, font, radius, bayangan). Usulkan perubahan lewat review.
- File di luar folder tema: `resources/js/Core`, `resources/js/pages`, `resources/js/Builder`, kode PHP, dan konfigurasi.
- Dependensi (`package.json`, `composer.json`).

Kalau perubahan yang diminta butuh data baru di props, berhenti dan minta pengembang menambahkannya di server.

## 2. Mobile-first (wajib)

Tulis class dasar untuk layar ponsel 360px lebih dulu, lalu tambahkan breakpoint ke atas: `sm:` (640px), `md:` (768px), `lg:` (1024px), `xl:` (1280px). Jangan mendesain untuk desktop lalu "mengecilkan".

```html
<!-- Benar: satu kolom di ponsel, dua kolom mulai sm, tiga mulai lg -->
<ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <!-- Salah: tiga kolom dulu, lalu dipaksa satu kolom di ponsel -->
    <ul class="grid grid-cols-3 gap-4 max-sm:grid-cols-1"></ul>
</ul>
```

Aturan ukuran:

- Target sentuh minimal 24×24px; tombol dan tautan aksi utama 44px tinggi (`h-11`, `min-h-11`), tombol cari 48px (`h-12`).
- Teks isi minimal 16px. Teks sekunder minimal 14px (`text-sm`) dengan warna `text-ink-muted`. Ukuran 12px (`text-xs`) hanya untuk badge.
- Tidak boleh ada scroll horizontal di halaman. Deret yang memang digeser (karusel) harus menggeser isinya sendiri, bukan halaman.

## 3. Memakai token desain

Pakai class token dari `theme.css`, jangan pernah kode warna langsung.

```html
<!-- Benar -->
<p class="text-ink-muted">…</p>
<a class="bg-brand-700 text-white hover:bg-brand-800">…</a>

<!-- Salah -->
<p class="text-[#555]">…</p>
<a style="background:#1d4ed8">…</a>
```

Ringkasan dari `DESIGN.md`:

- **Navy** (`brand-900`, `brand-950`) untuk header, footer, banner, dan judul.
- **Emas** (`gold-400`) hanya untuk aksi utama **di atas bidang navy** dan cincin fokus. Di bidang terang, aksi utama memakai `brand-700`.
- **Kartu**: `rounded-card`, `bg-canvas`, `ring-1 ring-line`, `shadow-card`; naik ke `shadow-raised` saat hover.
- **Ikon**: hanya Lucide (garis, satu ketebalan). Ikon selalu ditemani teks atau label `sr-only`, dan ikonnya diberi `aria-hidden="true"`. Jangan memakai emoji atau karakter Unicode sebagai ikon.
- **Judul** bagian tidak diberi label kecil berhuruf kapital di atasnya (eyebrow).

## 4. Aksesibilitas (WCAG 2.2 AA)

Periksa setiap perubahan dengan daftar ini:

- [ ] HTML semantik: `header`, `nav`, `main`, `section`, `article`, `footer`, dan heading berurutan (h1 → h2 → h3, tidak melompat).
- [ ] Kontras teks minimal 4.5:1 (3:1 untuk teks besar). Pakai pasangan warna dari `DESIGN.md`; jangan menaruh teks `ink-muted` di atas navy.
- [ ] Fokus keyboard selalu terlihat. Jangan menulis `outline-none` atau `focus:outline-none` tanpa pengganti yang terlihat.
- [ ] Semua bisa dioperasikan dengan keyboard (Tab, Shift+Tab, Enter, Spasi, Esc).
- [ ] Gambar punya `alt` (kosong `alt=""` bila hanya hiasan), ukuran atau `aspect-*` yang jelas, dan `loading="lazy"` kecuali gambar paling atas.
- [ ] Tautan yang membuka tab baru memakai `rel="noopener noreferrer"` dan keterangan `sr-only` "(membuka tab baru)".
- [ ] Status tidak dibedakan dengan warna saja: sertakan teks atau ikon (contoh: badge Berita Dinas / Media Center).
- [ ] Animasi menghormati `prefers-reduced-motion` (sudah diatur global di `theme.css`; jangan menimpanya).

### Karusel dan slider

Blok `HeroSlider` dan `NewsCarousel` mengikuti pola karusel WAI-ARIA. Saat mengubah template-nya, pertahankan:

- `aria-roledescription="carousel"` pada pembungkus dan `aria-roledescription="slide"` dengan label "n dari m" pada setiap slide.
- Tombol sebelumnya/berikutnya setinggi 44px dengan label `sr-only`.
- Tombol jeda di HeroSlider selama slide bergeser otomatis. Logika berhenti saat kursor atau fokus berada di dalam karusel, dan saat pengguna memilih gerak dikurangi, ada di `<script setup>`; jangan dihapus.
- NewsCarousel tidak pernah bergeser otomatis.
- Banner tunggal (`HeroBanner`) tetap pilihan utama untuk beranda; HeroSlider hanya dipakai bila memang ada beberapa pesan penting.

## 5. Keamanan saat mengubah template

- Teks dari luar (judul dan ringkasan Media Center, input pengelola) ditampilkan dengan `{{ }}`, **tidak pernah** dengan `v-html`.
- `v-html` hanya untuk HTML yang sudah disanitasi server (isi artikel, blok Teks), dan hanya di tempat yang sudah memakainya.
- Jangan menulis path atau URL route secara manual. Pakai `site.links` dan URL dari data blok (`data.index_url`, `item.url`).
- Jangan menambah `<script>`, `style` inline berisi data, atau sumber eksternal (font, CDN, iframe). Kebijakan keamanan (CSP) akan memblokirnya.

## 6. Alur kerja

1. Jalankan lingkungan lokal: `composer run dev` (atau `npm run dev` bila server Laravel sudah berjalan).
2. Ubah template di folder tema.
3. Periksa di browser pada lebar **360, 768, 1024, dan 1280px**, termasuk dengan keyboard saja.
4. Jalankan pemeriksa format dan lint:

    ```bash
    npm run check:fix
    ```

5. Jalankan pemeriksa tipe:

    ```bash
    npm run types:check
    ```

6. Ajukan perubahan untuk review dengan tangkapan layar ponsel dan desktop.

Pengembang yang mereview mengecek: batas wewenang (bagian 1), daftar aksesibilitas (bagian 4), dan tidak ada nilai warna mentah.

## 7. Contoh

| Permintaan                                          | Boleh dikerjakan magang?                           |
| --------------------------------------------------- | -------------------------------------------------- |
| Mengganti jarak antar-kartu dari `gap-4` ke `gap-6` | Ya                                                 |
| Menukar urutan judul dan tanggal di kartu berita    | Ya, selama heading tetap berurutan                 |
| Menambah ikon Lucide di tombol                      | Ya, dengan `aria-hidden` dan teks tombol tetap ada |
| Mengubah warna navy tema                            | Tidak; usulkan lewat review `theme.css`            |
| Menampilkan jumlah pengunjung di kartu              | Tidak; butuh data baru dari server                 |
| Mengganti `{{ item.title }}` menjadi `v-html`       | Tidak pernah                                       |

## 8. Untuk pengembang: menambah blok baru

Ikuti langkah di panduan arsitektur (`.ai/guidelines/moso-architecture.md`, bagian Page Builder):

1. Kelas blok di `app/Services/PageBuilder/Blocks` (turunan `AbstractBlock`, nilai bawaan di `defaults()`).
2. Daftarkan di `BlockRegistry::BLOCKS`.
3. Tipe props dan data di `resources/js/types/blocks.ts`.
4. Ikon dan ringkasan di `resources/js/Builder/blockDefinitions.ts`, inspector di `resources/js/Builder/inspectors`.
5. Komponen tema `Themes/{Tema}/Components/{type}.vue` dengan komentar peringatan magang dan `defineProps` bertipe ketat.
6. Tes: validasi props, hidrasi data, dan penyimpanan lewat Page Builder.
