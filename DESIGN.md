---
name: Moso TemaModern
description: Tema publik portal dinas Pemerintah Kota Singkawang, biru pemerintah yang resmi dan dipoles modern.
colors:
    brand-950: 'oklch(0.215 0.065 262)'
    brand-900: 'oklch(0.285 0.095 261)'
    brand-800: 'oklch(0.35 0.125 261)'
    brand-700: 'oklch(0.425 0.15 260)'
    brand-600: 'oklch(0.49 0.165 259)'
    brand-300: 'oklch(0.78 0.095 255)'
    brand-200: 'oklch(0.875 0.058 254)'
    brand-100: 'oklch(0.935 0.032 252)'
    brand-50: 'oklch(0.972 0.014 252)'
    gold-400: 'oklch(0.84 0.16 84)'
    gold-300: 'oklch(0.9 0.13 90)'
    gold-200: 'oklch(0.94 0.08 92)'
    ink: 'oklch(0.225 0.03 260)'
    ink-muted: 'oklch(0.44 0.03 258)'
    canvas: 'oklch(1 0 0)'
    mist: 'oklch(0.973 0.009 250)'
    line: 'oklch(0.905 0.014 252)'
    mc: 'oklch(0.43 0.085 182)'
    mc-soft: 'oklch(0.955 0.03 182)'
    ok: 'oklch(0.45 0.11 155)'
    ok-soft: 'oklch(0.955 0.04 155)'
typography:
    display:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '2rem (sm 3rem, lg 3.5rem)'
        fontWeight: 800
        lineHeight: 1.12
        letterSpacing: '-0.025em'
    headline:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1.875rem (sm 2.25rem sampai 2.75rem)'
        fontWeight: 800
        lineHeight: 1.15
        letterSpacing: '-0.025em'
    section-title:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1.5rem (sm 1.875rem)'
        fontWeight: 800
        lineHeight: 1.25
        letterSpacing: '-0.025em'
    title:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1.125rem'
        fontWeight: 700
        lineHeight: 1.375
    body:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1rem'
        fontWeight: 400
        lineHeight: 1.625
    body-reading:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1.0625rem'
        fontWeight: 400
        lineHeight: 1.75
    body-sm:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '0.95rem'
        fontWeight: 400
        lineHeight: 1.625
    label:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '0.875rem'
        fontWeight: 600
        lineHeight: 1.43
    badge:
        fontFamily: 'Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif'
        fontSize: '0.75rem'
        fontWeight: 600
        lineHeight: 1.33
rounded:
    item: '0.5rem'
    control: '0.75rem'
    card: '1rem'
    pill: '9999px'
spacing:
    gutter-mobile: '16px'
    gutter-tablet: '24px'
    gutter-desktop: '32px'
    card-pad-mobile: '20px'
    card-pad: '24px'
    grid-gap: '16px'
    grid-gap-lg: '20px'
    section-mobile: '56px'
    section: '80px'
    container: '80rem'
components:
    button-primary:
        backgroundColor: '{colors.gold-400}'
        textColor: '{colors.brand-950}'
        rounded: '{rounded.pill}'
        padding: '0 20px'
        height: '44px'
    button-primary-hover:
        backgroundColor: '{colors.gold-300}'
        textColor: '{colors.brand-950}'
    button-brand:
        backgroundColor: '{colors.brand-700}'
        textColor: '{colors.canvas}'
        rounded: '{rounded.pill}'
        padding: '0 28px'
        height: '48px'
    button-brand-hover:
        backgroundColor: '{colors.brand-800}'
    button-outline:
        backgroundColor: '{colors.canvas}'
        textColor: '{colors.brand-700}'
        rounded: '{rounded.pill}'
        padding: '0 20px'
        height: '44px'
    input-search:
        backgroundColor: '{colors.canvas}'
        textColor: '{colors.ink}'
        rounded: '{rounded.pill}'
        padding: '0 16px 0 48px'
        height: '48px'
    card:
        backgroundColor: '{colors.canvas}'
        textColor: '{colors.ink}'
        rounded: '{rounded.card}'
        padding: '20px'
    card-title:
        textColor: '{colors.brand-950}'
        typography: '{typography.title}'
    badge-local:
        backgroundColor: '{colors.brand-50}'
        textColor: '{colors.brand-700}'
        typography: '{typography.badge}'
        rounded: '{rounded.pill}'
        padding: '4px 10px'
    badge-media-center:
        backgroundColor: '{colors.mc-soft}'
        textColor: '{colors.mc}'
        typography: '{typography.badge}'
        rounded: '{rounded.pill}'
        padding: '4px 10px'
    chip-meta:
        backgroundColor: '{colors.mist}'
        textColor: '{colors.ink}'
        rounded: '{rounded.pill}'
        padding: '6px 12px'
    chip-free:
        backgroundColor: '{colors.ok-soft}'
        textColor: '{colors.ok}'
        rounded: '{rounded.pill}'
        padding: '6px 12px'
    nav-link:
        textColor: '{colors.brand-50}'
        rounded: '{rounded.item}'
        padding: '0 12px'
        height: '44px'
    icon-tile:
        backgroundColor: '{colors.brand-50}'
        textColor: '{colors.brand-700}'
        rounded: '{rounded.control}'
        size: '48px'
    icon-tile-hover:
        backgroundColor: '{colors.brand-700}'
        textColor: '{colors.canvas}'
---

# Design System: Moso TemaModern

## Overview

**Creative North Star: "Loket Resmi yang Rapi"**

TemaModern adalah portal pemda standar yang dikerjakan dengan teliti: strip "situs resmi" dan header biru pemerintah tua di atas, banner foto bertirai navy, lalu layanan, berita, dan agenda dalam kartu putih yang padat informasi. Bentuknya sengaja akrab bagi warga (kartu standar portal pemda), dan kualitasnya datang dari pengerjaan: tipografi tegas Plus Jakarta Sans, kontras tinggi, sudut 16px yang konsisten, bayangan lembut berarah, dan ikon garis Lucide satu ketebalan yang selalu berpasangan dengan teks.

Kepadatannya sedang: setiap kartu membawa informasi nyata (jangka waktu, biaya, sumber berita, tanggal), bukan dekorasi. Warna bekerja dalam dua bidang: navy untuk bingkai resmi (strip, header, banner, footer, kepala halaman layanan) dan putih atau biru-abu sangat muda untuk bidang baca. Kuning emas adalah satu-satunya aksen hangat dan hanya muncul sebagai aksi utama di atas navy, penanda halaman aktif, dan cincin fokus.

Tema ini netral terhadap dinas: nama, tagline, logo Pemkot dan logo dinas, kontak, dan menu datang dari Pengaturan, tidak pernah ditanam di tema. Bila logo belum diunggah, tampil lambang netral (ikon Landmark di petak kaca). Tema ini tidak memuat raster apa pun; foto banner dan berita adalah konten.

Penolakan yang dikonfirmasi oleh tesis: slider berkedip, ikon clip-art, teks kecil abu-abu, dan kartu seragam tanpa informasi.

**Key Characteristics:**

- Bingkai navy resmi (brand-950 dan brand-900) di atas dan bawah, bidang baca putih dan mist di tengah.
- Satu aksen emas, hanya di atas navy atau sebagai fokus dan penanda aktif.
- Kartu putih bersudut 16px dengan cincin 1px warna line dan bayangan lembut dua lapis yang naik saat hover.
- Tombol, kolom cari, badge, dan chip berbentuk pil.
- Ikon garis Lucide berwarna brand-600 di bidang terang, selalu bersama teks atau label pembaca layar.
- Mobile-first dan WCAG 2.2 AA: target sentuh minimal 44px untuk aksi, fokus dua warna selalu terlihat.

## Colors

Palet biru pemerintah bertingkat dengan satu aksen emas dan dua warna semantik kecil untuk sumber berita dan status gratis. Semua nilai didefinisikan sekali di `theme.css` (blok `@theme`) dalam OKLCH; komponen hanya memakai class token, tidak pernah hex.

### Primary

- **Navy Balai Kota** (brand-950): strip "situs resmi", footer, teks judul di bidang terang (semua h1, h2, h3 kartu), dan warna teks di atas emas. Warna paling gelap dan paling "resmi".
- **Biru Pemerintah Tua** (brand-900): bilah header utama, latar banner tanpa foto, kepala halaman layanan, petak tanggal agenda, dan bulatan nomor langkah prosedur.
- **Biru Dinas** (brand-700): tombol utama di bidang terang, tautan teks, warna teks badge Berita Dinas, dan petak ikon akses cepat saat hover. Hover-nya turun ke brand-800.
- **Biru Ikon** (brand-600): ikon garis di bidang terang (Timer, Wallet, Clock, MapPin), penanda daftar di artikel, dan caret input.
- **Biru Garis Halus** (brand-300): ikon di atas navy, cincin kartu saat hover, garis kutipan, dan dekorasi garis bawah tautan pada varian banner muted.
- **Biru Embun** (brand-200, brand-100, brand-50): teks sekunder di atas navy (brand-200 dan brand-100), latar badge Berita Dinas, petak ikon akses cepat, dan latar sorot item menu dropdown (brand-50).

### Secondary

- **Emas Penanda** (gold-400): tombol aksi utama di atas navy (Cari layanan, Cari, Sampaikan pengaduan), garis bawah menu aktif di header, tautan lewati konten, dan cincin luar fokus keyboard. Hover-nya naik ke gold-300.
- **Emas Terang** (gold-300, gold-200): hover tombol emas, ikon Landmark di strip resmi, menu aktif di ponsel, latar seleksi teks (gold-300), dan hover tautan CTA bergaris bawah di banner (gold-200).

### Tertiary

- **Hijau Laut Media Center** (mc, mc-soft): hanya untuk badge sumber Media Center dan teks "Baca di Media Center". Bukan warna dekorasi.
- **Hijau Gratis** (ok, ok-soft): chip biaya "Gratis", ikon centang persyaratan, dan nilai biaya gratis di ringkasan layanan.

### Neutral

- **Tinta** (ink): teks isi utama.
- **Tinta Redup** (ink-muted): deskripsi, ringkasan kartu, tanggal, remah roti, dan placeholder. Tetap gelap (L 0.44), bukan abu-abu pucat.
- **Kanvas** (canvas): latar halaman, kartu, kolom cari, dan panel dropdown.
- **Kabut** (mist): bidang selang-seling (bagian berita, kepala halaman artikel, hasil layanan), chip meta, header tabel, dan kotak keadaan kosong.
- **Garis** (line): cincin kartu 1px, pembatas daftar, garis atas footer konten, dan garis timeline prosedur.

### Named Rules

**The Gold Stays on Navy Rule.** Emas tidak pernah menjadi isian tombol di bidang terang. Di bidang terang, aksi utama memakai brand-700 dengan teks putih (lihat varian banner muted dan formulir LayananList). Emas di bidang terang hanya muncul sebagai cincin fokus, seleksi teks, dan tautan lewati konten.

**The Navy Frame Rule.** Navy (brand-950, brand-900) membingkai halaman: strip, header, banner, kepala halaman layanan, footer. Bidang di antaranya selalu canvas atau mist, berselang-seling per bagian.

**The Source Is Not a Color Rule.** Sumber berita dibedakan dengan ikon dan teks (Landmark + "Berita Dinas", RadioTower + "Media Center"), warna hanya penguat (WCAG 1.4.1).

## Typography

**Display Font:** Plus Jakarta Sans (dengan ui-sans-serif, system-ui, sans-serif)
**Body Font:** Plus Jakarta Sans (dengan ui-sans-serif, system-ui, sans-serif)

**Character:** Satu keluarga untuk semua peran. Judul memakai ExtraBold (800) dengan tracking rapat sehingga terasa tegas dan resmi; isi memakai Regular (400) dengan leading longgar agar mudah dibaca warga di ponsel. Font di-self-host lewat opsi `fonts` laravel-vite-plugin (alias `jakarta`, bobot 400 sampai 800, preload 400 dan 700) dan dirender per tema lewat `@fonts`.

### Hierarchy

- **Display** (800, 2rem naik ke 3rem di sm dan 3.5rem di lg, line-height 1.12, tracking rapat): judul banner beranda saja.
- **Headline** (800, 1.875rem naik ke 2.25rem sampai 2.75rem, line-height 1.15 sampai 1.25, tracking rapat): h1 halaman tanpa banner, halaman layanan, dan artikel.
- **Section Title** (800, 1.5rem naik ke 1.875rem, line-height 1.25, tracking rapat): judul bagian lewat SectionHeader (Layanan Publik, Berita Terkini, Agenda Kegiatan) dan judul Persyaratan/Prosedur.
- **Title** (700, 1.125rem, line-height 1.375): judul kartu layanan, agenda, dan berita; kartu berita unggulan membesar ke 1.25rem sampai 1.75rem.
- **Body** (400, 1rem, line-height 1.625): deskripsi bagian dan teks umum; subjudul banner memakai 1.125rem sampai 1.25rem.
- **Body Reading** (400, 1.0625rem, line-height 1.75, maks. 70ch): isi artikel dan blok Teks (`.prose-moso`), butir persyaratan dan langkah prosedur.
- **Body Small** (400, 0.95rem, line-height 1.625): ringkasan kartu, detail agenda, item menu tingkat tiga.
- **Label** (600, 0.875rem): tanggal, remah roti, chip meta, tautan "Baca di Media Center".
- **Badge** (600, 0.75rem): badge sumber berita, serta teks pendek di atas navy (strip resmi dan tagline di ponsel, singkatan bulan di petak tanggal).

### Named Rules

**The Heavy Heading Rule.** Semua judul h1 dan h2 memakai bobot 800 dengan tracking rapat dan warna brand-950 (atau putih di atas navy), dan `text-wrap: balance`. Judul tidak pernah tipis atau abu-abu.

**The No Small Grey Text Rule.** Teks sekunder di bidang terang memakai ink-muted (gelap) pada ukuran minimal 0.875rem. Ukuran 0.75rem hanya untuk badge sumber dan teks pendek di atas navy (strip resmi dan tagline di ponsel, singkatan bulan di petak tanggal). Angka dan waktu memakai `tabular-nums`.

## Layout

Mobile-first. Kontainer tunggal `max-w-7xl` (80rem) dengan gutter 16px, 24px di sm, dan 32px di lg; halaman artikel menyempit ke 56rem untuk kolom baca. Bagian beranda bertumpuk vertikal dengan padding 56px di ponsel dan 80px mulai sm, berselang-seling canvas dan mist.

Grid kartu: layanan satu kolom di ponsel, dua di sm, tiga di lg (gap 16px, 20px di lg). Berita: satu kartu unggulan selebar penuh (di lg terbelah 7/5 antara foto dan teks), lalu grid dua kolom mulai sm. Kartu berita biasa di ponsel berbentuk baris horizontal dengan foto 112px di kiri agar daftar tetap rapat; mulai sm berubah menjadi kartu vertikal dengan foto 16:10. Akses cepat di ponsel menaruh ikon di atas label, dengan isi petak rata atas agar ikon sebaris sejajar. Tiga tautan tampil satu baris tiga kolom dan ikon pindah ke samping label mulai md. Jumlah lain memakai dua kolom dengan ikon di samping label mulai sm, lalu empat kolom mulai lg bila tautannya empat atau lebih. Label panjang membungkus di dalam petak dan tidak pernah meluap. Agenda dua kolom mulai md.

Halaman layanan: grid 12 kolom di lg, isi 8 kolom dan ringkasan 4 kolom yang menempel (sticky) di kanan; di ponsel ringkasan tampil lebih dulu. Footer: satu kolom, dua di md, 5/3/4 di lg.

Banner berfoto: tinggi minimal 26rem (30rem di sm, 34rem di lg), teks di bawah di ponsel dan di tengah vertikal di lg, sehingga daftar layanan dimulai dekat lipatan pertama di ponsel. Target uji 360, 768, 1024, dan 1280px tanpa scroll horizontal.

## Elevation & Depth

Sistem hibrid: kedalaman utama datang dari pelapisan tonal (navy, canvas, mist) dan cincin 1px warna line; bayangan lembut berarah ke bawah menambah sedikit angkat pada kartu. Ada dua tingkat bayangan, keduanya diwarnai navy transparan, bukan hitam.

### Shadow Vocabulary

- **Card** (`box-shadow: 0 1px 2px oklch(0.25 0.05 260 / 0.06), 0 6px 20px -8px oklch(0.25 0.05 260 / 0.14)`): keadaan diam semua kartu (layanan, berita, akses cepat, agenda, ringkasan layanan) dan foto artikel.
- **Raised** (`box-shadow: 0 2px 4px oklch(0.25 0.05 260 / 0.08), 0 18px 40px -14px oklch(0.25 0.06 260 / 0.28)`): hover kartu, panel dropdown menu, kolom cari di banner, tombol CTA di banner, dan tautan lewati konten.

### Named Rules

**The Lift on Intent Rule.** Kartu diam di tingkat Card dan naik ke Raised hanya saat hover (300ms, ease-out-expo), disertai cincin yang berubah ke brand-300. Tidak ada bayangan keras, offset tajam, atau glow berwarna.

## Shapes

Sudut membulat lembut dan konsisten. Kartu, panel dropdown, kotak keadaan kosong, gambar artikel, dan kotak pengaduan footer memakai sudut 16px (radius card). Petak ikon, tombol menu ponsel, lambang netral, dan petak tanggal agenda memakai 12px. Item menu dan tautan di dalam dropdown memakai 8px. Semua tombol, kolom cari, badge, chip, dan tautan media sosial berbentuk pil penuh. Tepi kartu digambar dengan cincin 1px (ring), bukan border tebal. Bentuk khas: bulatan nomor navy 40px pada timeline prosedur yang dihubungkan garis 1px.

## Components

### Buttons

Tegas dan ramah: pil tebal dengan teks bold.

- **Shape:** pil penuh (9999px), tinggi 44px untuk aksi biasa dan 48px untuk tombol cari dan aksi utama ponsel.
- **Primary (di atas navy):** isian gold-400, teks brand-950, padding horizontal 20px sampai 28px, sering dengan ikon Lucide 16px di depan (Search) atau di belakang (ExternalLink, ArrowRight).
- **Brand (di bidang terang):** isian brand-700, teks putih; hover brand-800.
- **Outline:** latar canvas, teks brand-700, cincin 1px line yang berubah ke brand-300 saat hover (paginasi, tombol telepon).
- **Hover / Focus:** transisi warna; panah di tautan bergerak 4px ke arah tujuan (300ms, ease-out-expo). Fokus memakai cincin global: outline 3px gold-400 berjarak 2px dengan bayangan dalam 2px brand-950.
- **Tautan aksi teks:** bold brand-700, hover brand-900, tinggi minimal 44px, dengan panah ArrowRight atau ArrowLeft.

### Chips

- **Meta layanan:** pil mist dengan teks ink 0.875rem dan ikon brand-600 (Timer, Wallet).
- **Gratis:** pil ok-soft dengan teks ok semibold dan ikon CircleCheck.
- **Kategori artikel:** pil canvas, teks brand-800 semibold, cincin 1px line.

### Cards / Containers

- **Corner Style:** 16px.
- **Background:** canvas di atas bidang canvas atau mist.
- **Shadow Strategy:** Card saat diam, Raised saat hover (lihat Elevation & Depth).
- **Border:** cincin 1px line; hover brand-300 pada kartu layanan dan akses cepat.
- **Internal Padding:** 20px di ponsel, 24px mulai sm; kartu berita biasa 16px lalu 20px; kartu unggulan hingga 40px di lg.
- **Seluruh kartu dapat diklik** lewat pseudo-element pada tautan judul; fokusnya digambar sebagai outline brand-700 bersudut kartu.

### Inputs / Fields

- **Style:** kolom cari pil setinggi 48px, latar canvas, teks ink 1rem, ikon Search ink-muted di kiri (padding kiri 48px). Di atas navy mulai sm, kolom dan tombol Cari menyatu dalam satu wadah pil canvas berbayangan Raised dengan padding 6px; di ponsel keduanya bertumpuk. Di bidang terang, kolom memakai border 1px line yang berubah ke brand-300 saat hover.
- **Focus:** cincin fokus global emas dan navy.

### Navigation

- **Header:** strip brand-950 setinggi baris kecil (ikon Landmark emas + "Situs resmi ..." + kontak mulai md), lalu bilah brand-900 dengan SiteBrand (dua slot logo + nama situs bold + tagline brand-200).
- **Menu desktop (lg):** tautan brand-50 semibold 0.95rem, tinggi 44px, sudut 8px, hover latar putih 10%. Halaman aktif ditandai garis bawah gold-400 2px berjarak 8px. Dropdown: panel canvas bersudut 16px, bayangan Raised, cincin line, lebar 288px, item 8px dengan sorot brand-50.
- **Ponsel:** tombol menu 44px kaca (putih 10%, cincin putih 15%). Panel membuka di bawah header dengan tombol emas "Cari layanan" selebar penuh setinggi 48px, item 48px dipisah garis putih 10%, submenu memakai `details`, dan kontak di bagian bawah.
- **Remah roti:** 0.875rem, tautan semibold (brand-700 di bidang terang, putih di atas navy), pemisah "/".

### Source Badge (Signature)

Pil kecil berikon yang wajib menyertai setiap item berita: Landmark + "Berita Dinas" (brand-50 / brand-700) atau RadioTower + "Media Center" (mc-soft / mc). Berita Media Center ditambah teks "Baca di Media Center" dengan ikon ExternalLink dan keterangan pembaca layar.

### Agenda Date Tile (Signature)

Petak tanggal navy brand-900 bersudut 12px, lebar 64px sampai 72px: singkatan hari (kapital) di atas, angka tanggal 800 besar, singkatan bulan (kapital) di bawah, dengan tanggal lengkap untuk pembaca layar. Huruf kapital di sini adalah bahasa kalender, bukan label hias.

### Rich Text (.prose-moso)

Isi HTML dari editor yang sudah disanitasi server: maks. 70ch, 1.0625rem dengan leading 1.75, h2/h3 bold brand-950, tautan brand-700 bergaris bawah, penanda daftar brand-600, kutipan bergaris kiri brand-300, tabel bergulir horizontal dengan header mist, gambar bersudut 16px.

## Do's and Don'ts

### Do:

- **Do** memakai token dari `theme.css` lewat class Tailwind (bg-brand-900, text-ink-muted, rounded-card, shadow-card); ubah nilai hanya di blok `@theme`.
- **Do** menulis class dasar untuk ponsel 360px lebih dulu lalu menambah `sm:`, `md:`, `lg:`; aksi utama setinggi 44px sampai 48px.
- **Do** memasangkan setiap ikon Lucide (garis, satu ketebalan) dengan teks atau label `sr-only`, dan memberi `aria-hidden` pada ikonnya.
- **Do** menampilkan SourceBadge pada setiap item berita, dan merender teks Media Center dengan interpolasi `{{ }}`.
- **Do** memakai emas (gold-400, hover gold-300) untuk aksi utama hanya di atas navy, dan brand-700 untuk aksi utama di bidang terang.
- **Do** menaruh informasi nyata di kartu: jangka waktu, biaya, sumber, tanggal.
- **Do** mengambil nama, logo, kontak, dan menu dari Pengaturan; tema tetap netral terhadap dinas.

### Don't:

- **Don't** menulis kode warna hex atau nilai warna mentah di komponen tema.
- **Don't** mengganti banner foto tunggal dengan slider yang berputar atau berkedip.
- **Don't** memakai ikon clip-art, ikon berwarna penuh, atau glyph/emoji sebagai ikon.
- **Don't** memakai teks kecil abu-abu pucat; teks sekunder di bidang terang tetap ink-muted dan minimal 0.875rem.
- **Don't** membuat kartu seragam yang hanya berisi judul tanpa informasi.
- **Don't** menambah label hias berhuruf kapital (eyebrow/kicker) di atas judul bagian.
- **Don't** memakai bayangan keras, offset tajam, atau bayangan hitam; hanya Card dan Raised.
- **Don't** memakai `v-html` kecuali untuk HTML yang sudah disanitasi server (`.prose-moso`), dan jangan mengubah `<script setup>` komponen tema.
