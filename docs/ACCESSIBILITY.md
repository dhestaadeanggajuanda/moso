# Aksesibilitas (WCAG 2.2 AA)

Target situs publik moso adalah **WCAG 2.2 level AA**. Kewajiban itu dipenuhi oleh tema itu sendiri: HTML semantik, kontras warna, keyboard, fokus yang terlihat, teks alternatif, dan gerak yang bisa dihentikan. Widget aksesibilitas (`packages/a11y-widget`) adalah **pelengkap** bagi pengunjung yang butuh penyesuaian tambahan, **bukan pengganti** tema yang patuh AA. Tema yang gagal AA tetap dianggap gagal walau widget terpasang.

Dokumen terkait:

- `docs/SOP-FRONTEND.md`: daftar periksa WCAG 2.2 AA untuk tim magang dan aturan karusel.
- `DESIGN.md`: sistem desain dan token warna TemaModern.
- `packages/a11y-widget/README.md`: referensi teknis widget (opsi, event, build).

## 1. Kewajiban tema

Ringkasan dari `.ai/guidelines/frontend-themes.md` dan SOP frontend:

| Kriteria                             | Yang harus dipenuhi tema                                                                           |
| ------------------------------------ | -------------------------------------------------------------------------------------------------- |
| 1.1.1 Konten non-teks                | Setiap gambar punya `alt`; gambar dekoratif `alt=""` atau `aria-hidden`                            |
| 1.3.1 Info dan relasi                | `header`, `nav`, `main`, `article`, `footer`, heading berurutan, tabel dengan `th`                 |
| 1.4.3 / 1.4.11 Kontras               | Teks ≥ 4.5:1 (teks besar ≥ 3:1), komponen dan ikon ≥ 3:1. Warna hanya dari token `theme.css`       |
| 1.4.4 / 1.4.10 Ubah ukuran, reflow   | Tetap terbaca di zoom 200% dan lebar 320px tanpa scroll horizontal                                 |
| 2.1.1 Keyboard, 2.4.7 / 2.4.11 Fokus | Semua kontrol bisa dipakai dengan keyboard; cincin fokus selalu terlihat dan tidak tertutup header |
| 2.2.2 Jeda, hentikan, sembunyikan    | Karusel punya tombol jeda, tidak bergerak sendiri saat `prefers-reduced-motion`                    |
| 2.4.1 Lewati blok                    | Skip link ke `#konten`                                                                             |
| 2.5.8 Ukuran target                  | Target sentuh ≥ 24×24px; tombol utama di ponsel ≥ 44px                                             |
| 3.1.1 Bahasa halaman                 | `<html lang="id">`                                                                                 |

## 2. Widget aksesibilitas

Widget berupa Web Component `<a11y-widget>` dengan Shadow DOM, ditulis dengan TypeScript tanpa framework, sehingga bisa dipasang di aplikasi apa pun (Laravel, Vue, React, HTML statis).

### Fitur

| Fitur                                   | Fungsi                                                                                   | Membantu kriteria    |
| --------------------------------------- | ---------------------------------------------------------------------------------------- | -------------------- |
| Ukuran teks                             | 100%, 115%, 130%, 150% dari ukuran dasar halaman                                         | 1.4.4                |
| Spasi teks lebih lega                   | Tinggi baris 1.5, jarak paragraf 2×, jarak huruf 0.12em, jarak kata 0.16em               | 1.4.12               |
| Font mudah dibaca / ramah disleksia     | Atkinson Hyperlegible atau OpenDyslexic (dipilih salah satu, dimuat saat dipilih)        | Kebutuhan kognitif   |
| Sorot tautan                            | Semua tautan diberi garis bawah tebal, latar kuning pucat, dan garis tepi gelap          | 1.4.1                |
| Kontras tinggi, skala abu-abu, terbalik | Filter warna pada isi halaman (widget sendiri tidak terkena filter)                      | Kebutuhan low vision |
| Sembunyikan gambar                      | Gambar dan video disembunyikan (ruangnya tetap) agar halaman tidak ramai                 | Kebutuhan kognitif   |
| Hentikan animasi                        | Mematikan animasi dan transisi CSS; tema juga menghentikan karusel otomatis              | 2.2.2, 2.3.3         |
| Kursor besar                            | Kursor panah 48px dengan garis tepi putih                                                | Kebutuhan motorik    |
| Panduan baca                            | Pita horizontal yang mengikuti pointer untuk membantu fokus baris                        | Kebutuhan kognitif   |
| Bacakan teks yang diklik / terpilih     | Membacakan paragraf yang diklik atau teks yang diseleksi, dalam Bahasa Indonesia (id-ID) | Kebutuhan baca       |
| Atur ulang semua                        | Mengembalikan semua pengaturan dan menghapus data tersimpan                              |                      |

### Aksesibilitas widget sendiri

Widget wajib memenuhi AA:

- Tombol pemicu memakai `aria-expanded` dan `aria-controls`. Panel adalah `role="dialog"` berlabel; saat dibuka, fokus pindah ke judul panel.
- `Esc` menutup panel dan fokus kembali ke tombol pemicu.
- Tombol pilihan memakai `aria-pressed`, dan setiap perubahan diumumkan lewat region `aria-live`.
- Target minimal 44px, cincin fokus dua warna (amber dan gelap) yang terlihat di latar terang maupun gelap.
- Di layar di bawah 480px, panel melebar penuh dengan margin 8px. Tidak ada scroll horizontal.

### Privasi

- Preferensi hanya disimpan di `localStorage` perangkat pengunjung (key `moso-a11y-widget:v1`). Widget tidak mengirim data ke server dan tidak memakai cookie. Bila penyimpanan diblokir (mode privat), widget tetap bekerja sampai halaman ditutup.
- Fitur suara memakai Web Speech API bawaan browser. Suara lokal sistem diutamakan. Bila hanya tersedia suara daring (mis. suara Google di Chrome), browser mengirim teks yang dibacakan ke penyedianya. Teks yang dibacakan adalah isi halaman publik.

### Pemasangan di moso

`resources/js/lib/a11yWidget.ts` (dipanggil dari `resources/js/app.ts`) memasang widget hanya pada halaman `site/*`, dengan bahasa Indonesia di kanan bawah. Paket dimuat dinamis, jadi halaman admin dan render SSR tidak memuatnya. Saat pengunjung pindah ke halaman non-situs (mis. tombol Kembali ke halaman login), widget dilepas dan tampilan halaman dipulihkan.

Tema menyesuaikan diri dengan pengaturan widget lewat atribut di `<html>`:

- Warna aksen widget mengikuti tema lewat variabel CSS `--a11y-accent` dan `--a11y-accent-text` (TemaModern: `brand-900` dan `canvas`, di `theme.css`).
- Komponen bergerak membaca `html[data-a11y-motion="off"]`. Contohnya `HeroSlider.vue` (berhenti bergeser otomatis, dipantau dengan `MutationObserver`) dan `NewsCarousel.vue` (gulir tanpa animasi). Komponen tema baru yang bergerak wajib melakukan hal yang sama.

### Pemasangan di aplikasi lain

Build widget, lalu salin `a11y-widget.iife.js` beserta folder `fonts/` ke folder publik aplikasi tujuan:

```bash
npm run build:widget
```

```html
<script
    src="/vendor/moso/a11y-widget.iife.js"
    data-lang="id"
    data-position="bottom-right"
    defer
></script>
```

Font dimuat dari folder `fonts/` di samping skrip, tanpa CDN. Opsi lengkap (`data-*`, API `window.MosoA11yWidget`, event `a11y-change`) ada di README paket.

Bila aplikasi tujuan memakai CSP: widget tidak memakai inline script atau `eval`. Ia butuh `style-src 'unsafe-inline'` (gaya disisipkan ke `<head>` dan Shadow DOM), `font-src 'self'`, dan `img-src data:` (kursor besar). CSP moso sudah memenuhi ini tanpa perubahan.

## 3. Cara menguji

Otomatis:

```bash
npm run test:widget
```

Pengujian Vitest + happy-dom mencakup buka/tutup panel dengan fokus dan `Esc`, label bahasa Inggris, penyimpanan dan pemulihan preferensi, penyimpanan yang diblokir, batas ukuran teks, font eksklusif yang dimuat saat dipilih, atur ulang, pengumuman `aria-live`, data tersimpan yang rusak, pelepasan widget, dan pembacaan suara (paragraf dibaca, tautan tidak, suara lokal diutamakan). Pengujian ini ikut dijalankan `composer ci:check` di CI.

Manual, setiap kali tema atau widget berubah:

1. Navigasi dengan keyboard saja (Tab, Shift+Tab, Enter, Spasi, Esc) di beranda, detail berita, dan daftar layanan. Fokus harus selalu terlihat.
2. Jalankan pemeriksa otomatis (axe DevTools atau Lighthouse Accessibility) di halaman yang sama. Temuan kontras dan label wajib diperbaiki di tema.
3. Zoom browser 200% dan lebar 320px: tidak ada teks terpotong atau scroll horizontal.
4. Nyalakan `prefers-reduced-motion` di sistem operasi, lalu "Hentikan animasi" di widget: karusel tidak bergeser sendiri.
5. Uji widget di 390px dan 1280px: semua opsi, lalu "Atur ulang semua".
6. Bila memungkinkan, uji dengan pembaca layar (NVDA di Windows, TalkBack di Android).

## 4. Keterbatasan yang diketahui

- Filter kontras dipasang pada anak langsung `<body>`. Elemen `position: fixed` di dalamnya akan diposisikan relatif terhadap elemen yang difilter, bukan layar. Tema moso tidak bergantung pada hal ini, tetapi aplikasi lain perlu mengujinya.
- "Hentikan animasi" mematikan animasi CSS. Animasi JavaScript (mis. karusel) harus membaca `data-a11y-motion` sendiri seperti komponen TemaModern.
- Ketersediaan suara Bahasa Indonesia bergantung pada browser dan sistem operasi. Bila browser tidak mendukung Web Speech API, tombol suara dinonaktifkan dan diberi keterangan.
