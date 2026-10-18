# TemaModern

Tema publik bawaan moso: portal pemerintah daerah yang resmi dan dipoles modern. Netral terhadap dinas: nama, logo, kontak, dan menu diambil dari Pengaturan.

- Sistem desain: `DESIGN.md` di akar proyek.
- Aturan kerja tim magang: `docs/SOP-FRONTEND.md`.

## Isi folder

| Folder / file            | Isi                                                                                                                                                                                 |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `theme.css`              | Token desain (`@theme` Tailwind 4) dan gaya dasar. Hanya diubah lewat review.                                                                                                       |
| `Layouts/SiteLayout.vue` | Kerangka halaman: skip link, header, isi, footer.                                                                                                                                   |
| `Components/`            | Satu komponen per tipe blok Page Builder, namanya sama dengan `type` blok: HeroBanner, HeroSlider, AksesCepat, LayananList, NewsGridHybrid, NewsCarousel, AgendaKegiatan, RichText. |
| `Views/`                 | Halaman non-blok: detail dan indeks berita, daftar dan detail layanan, PPID, E-SAKIP.                                                                                               |
| `Partials/`              | Bagian bersama: header, footer, kartu berita dan layanan, badge sumber, format tanggal WIB.                                                                                         |

## Aturan singkat

- Hanya `<template>` dan class Tailwind yang boleh diubah tim magang; `<script setup>` dilarang disentuh.
- Mobile-first: class ponsel dulu, lalu `sm:`, `md:`, `lg:`.
- Tanpa kode warna mentah; pakai token (`bg-brand-900`, `text-ink-muted`, `rounded-card`).
- Teks dari luar selalu `{{ }}`, tidak pernah `v-html`.
- Tema hanya dikompilasi bila terdaftar di `VITE_THEMES` (lihat `.env.example`).
