# Moso: Aturan Frontend dan Tema

## Mobile-First (wajib)

- Tulis class dasar untuk layar ponsel (~360px) dulu, lalu tambahkan breakpoint ke atas: `sm:` → `md:` → `lg:` → `xl:`. Jangan mendesain untuk desktop lalu "mengecilkan".
- Uji tampilan di 360px, 768px, 1024px, dan 1280px. Tidak boleh ada scroll horizontal.
- Target sentuh minimal 24×24px (WCAG 2.5.8). Tombol utama sebaiknya ≥ 44px tinggi di ponsel.
- Gambar wajib punya `alt`, ukuran eksplisit atau `aspect-*`, dan `loading="lazy"` kecuali gambar hero.

## Kontrak komponen tema (`resources/js/Themes/{Tema}/Components`)

- Satu file per tipe blok, dengan nama sama persis dengan `type` blok (mis. `NewsGridHybrid.vue`).
- `<script setup lang="ts">` dengan `defineProps` bertipe ketat dari `@/types/blocks`. Komponen tema tidak boleh memanggil API, Inertia router, atau store, dan hanya merender props.
- Setiap komponen tema diawali komentar peringatan untuk tim magang: **hanya `<template>` dan class Tailwind yang boleh diubah; `<script setup>` dilarang disentuh.**
- Item berita memiliki `source: 'local' | 'media_center'`. Tampilkan badge sumber (`SourceBadge`) agar pembaca tahu asal berita.
- Teks dari sumber eksternal dirender dengan interpolasi `{{ }}`, tidak pernah dengan `v-html`.

## Aksesibilitas (WCAG 2.2 AA)

- Gunakan HTML semantik (`header`, `nav`, `main`, `article`, `footer`, heading berurutan) dan sediakan skip link ke `#konten`.
- Kontras teks minimal 4.5:1 (3:1 untuk teks besar). Fokus keyboard harus selalu terlihat.
- Carousel/slider: ada tombol jeda, bisa dioperasikan dengan keyboard, tidak auto-play saat `prefers-reduced-motion`, dan memakai `aria-roledescription="carousel"`.
- Widget aksesibilitas (`packages/a11y-widget`) adalah pelengkap, bukan pengganti tema yang patuh AA.

## Desain

- Gunakan skill `impeccable` untuk merancang dan mengaudit tema publik (`/impeccable craft`, `adapt`, `audit`, `polish`). Konteks produk ada di `PRODUCT.md`, sistem desain di `DESIGN.md`, dan aturan kerja tim magang di `docs/SOP-FRONTEND.md`.
- Karusel yang sudah ada (`HeroSlider`, `NewsCarousel`) menjadi acuan pola: tombol 44px berlabel, tombol jeda bila bergeser otomatis, berhenti saat fokus/hover, tidak bergerak saat `prefers-reduced-motion`, dan slide tidak aktif dibuat `inert`.
- Token desain (warna, font, radius) didefinisikan sekali di `Themes/{Tema}/theme.css` lewat `@theme` Tailwind 4. Jangan hardcode warna hex di komponen.
- Font di-self-host lewat opsi `fonts` laravel-vite-plugin (diunduh saat build), tanpa CDN font saat runtime.
- Tampilan halaman non-blok (indeks dan detail berita, daftar dan detail layanan, PPID, E-SAKIP) ada di `Themes/{Tema}/Views`; bagian bersama (header, footer, kepala halaman, kartu, baris dokumen, paginasi, badge) di `Partials`. Tema tidak menanam path route sendiri: pakai `site.links` dan URL dari data blok.
