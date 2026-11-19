# TemaModern2

Tema dengan dunia visual baru, "permukaan aplikasi hangat": latar pasir, kartu putih bersudut besar, satu warna aksi teal yang hanya dipakai elemen yang bisa ditekan, huruf Nunito Sans, bilah navigasi bawah di ponsel dan header atas di layar lebar. Netral terhadap dinas: nama, logo, kontak, dan menu diambil dari Pengaturan.

**Mengapa ada dua tema.** `TemaModern` sudah dirilis dan dipakai website. Kodenya ada di image, jadi mengubahnya mengubah tampilan semua website yang memakainya. Karena itu:

- `TemaModern` hanya menerima perbaikan bug dan keamanan, bukan perubahan desain.
- Perubahan desain dikerjakan di tema ini. Website pindah ke sini hanya bila admin memilihnya (Pengaturan `theme.active`, atau kolom Tema per halaman).
- Setelah dirilis, tema ini mengikuti aturan yang sama. Desain berikutnya dibuat sebagai tema baru (`TemaModern3`, dan seterusnya).
- Jangan menghapus atau mengganti urutan tema di `VITE_THEMES` selama masih ada website yang memakainya: website itu akan diam-diam jatuh ke tema pertama.

- Sistem desain: `DESIGN.md` di folder ini. `DESIGN.md` di akar proyek menggambarkan `TemaModern`, bukan tema ini. Perbarui berkas di folder ini bila desain berubah.
- Aturan kerja tim magang: `docs/SOP-FRONTEND.md`.

## Isi folder

| Folder / file            | Isi                                                                                                                                                                                 |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `theme.css`              | Token desain (`@theme` Tailwind 4) dan gaya dasar. Hanya diubah lewat review.                                                                                                       |
| `Layouts/SiteLayout.vue` | Kerangka halaman: skip link, header, isi, footer, dan bilah navigasi bawah di ponsel.                                                                                               |
| `Components/`            | Satu komponen per tipe blok Page Builder, namanya sama dengan `type` blok: HeroBanner, HeroSlider, AksesCepat, LayananList, NewsGridHybrid, NewsCarousel, AgendaKegiatan, RichText. |
| `Views/`                 | Halaman non-blok: detail dan indeks berita, daftar dan detail layanan, PPID, E-SAKIP.                                                                                               |
| `Partials/`              | Bagian bersama: header, footer, bilah navigasi bawah, kartu berita dan layanan, badge sumber, format tanggal WIB.                                                                   |

## Aturan singkat

- Hanya `<template>` dan class Tailwind yang boleh diubah tim magang; `<script setup>` dilarang disentuh.
- Mobile-first: class ponsel dulu, lalu `sm:`, `md:`, `lg:`.
- Tanpa kode warna mentah; pakai token (`bg-ground`, `bg-card`, `text-action`, `text-ink-muted`, `rounded-card`). Teal (`action`) hanya untuk elemen yang bisa ditekan.
- Kartu memakai bayangan saja, tidak bayangan plus garis.
- Teks dari luar selalu `{{ }}`, tidak pernah `v-html`.
- Tema hanya dikompilasi bila terdaftar di `VITE_THEMES` (lihat `.env.example`).
