---
name: TemaModern2
description: Permukaan aplikasi layanan yang hangat untuk situs dinas Kota Singkawang: pasir, kartu putih, satu teal untuk yang bisa ditekan.
colors:
  ground: "oklch(0.968 0.019 84)"
  ground-deep: "oklch(0.935 0.03 82)"
  card: "oklch(1 0 0)"
  ink: "oklch(0.26 0.014 75)"
  ink-muted: "oklch(0.46 0.022 78)"
  edge: "oklch(0.6 0.03 80)"
  line: "oklch(0.92 0.02 82)"
  action-50: "oklch(0.965 0.02 188)"
  action-100: "oklch(0.93 0.04 188)"
  action-200: "oklch(0.87 0.065 188)"
  action: "oklch(0.47 0.088 191)"
  action-hover: "oklch(0.4 0.08 192)"
  action-deep: "oklch(0.33 0.07 192)"
  clay: "oklch(0.45 0.11 48)"
  clay-soft: "oklch(0.945 0.035 70)"
  ok: "oklch(0.45 0.1 155)"
  ok-soft: "oklch(0.955 0.04 155)"
typography:
  display:
    fontFamily: "Nunito Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "3rem"
    fontWeight: 800
    lineHeight: 1.15
  headline:
    fontFamily: "Nunito Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 800
    lineHeight: 1.25
  title:
    fontFamily: "Nunito Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 800
    lineHeight: 1.375
  body:
    fontFamily: "Nunito Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.625
  label:
    fontFamily: "Nunito Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.33
rounded:
  card: "1.375rem"
  tile: "1.25rem"
  control: "1rem"
  icon: "0.875rem"
  pill: "999px"
components:
  button-action:
    backgroundColor: "{colors.action}"
    textColor: "{colors.card}"
    rounded: "{rounded.pill}"
    padding: "0 1.5rem"
    height: "3rem"
  button-action-hover:
    backgroundColor: "{colors.action-hover}"
  button-quiet:
    backgroundColor: "{colors.card}"
    textColor: "{colors.action}"
    rounded: "{rounded.pill}"
    padding: "0 1.25rem"
    height: "2.75rem"
  field:
    backgroundColor: "{colors.ground}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    height: "3rem"
    padding: "0 1rem"
  card:
    backgroundColor: "{colors.card}"
    textColor: "{colors.ink}"
    rounded: "{rounded.card}"
    padding: "1.25rem"
  source-badge-media-center:
    backgroundColor: "{colors.clay-soft}"
    textColor: "{colors.clay}"
    rounded: "{rounded.pill}"
  cost-badge-free:
    backgroundColor: "{colors.ok-soft}"
    textColor: "{colors.ok}"
    rounded: "{rounded.pill}"
---

# Design System: TemaModern2

Tema ini hidup berdampingan dengan TemaModern (navy dan emas), yang dibekukan dan dicatat di berkas desain akar proyek. Berkas ini hanya mencatat TemaModern2. Sumber kebenarannya adalah `theme.css` di folder ini; nilai warna, radius, bayangan, dan gerak di sini harus sama persis dengannya.

## Overview

**Creative North Star: "Lembar Kota"**

Situs dinas yang terasa seperti aplikasi layanan yang sudah akrab: satu kartu putih untuk satu gagasan, di atas pasir hangat. Kepribadiannya tenang, ramah, dan resmi tanpa kaku. Kepadatan sedang, teks besar (16px ke atas), target sentuh lapang, karena pembacanya warga di ponsel murah, di luar ruangan, dengan data terbatas. Identitas dinas (nama, logo, sapaan) selalu datang dari Pengaturan, bukan dari tema.

Dunia ini dikenali walau semua isinya dihapus: pasir, putih, satu teal. Tidak ada gambar raster yang dikirim oleh tema; hero halaman tersusun dari tipografi, kartu, dan kolom cari. Ikon adalah garis Lucide, dipasang sebagai SVG, bukan font ikon atau emoji.

Aturan yang menopang seluruh sistem: **teal berarti bisa ditekan.** Elemen yang nonaktif, informatif, atau dekoratif tidak berwarna teal.

**Key Characteristics:**

- Latar pasir hangat, kartu putih bersudut besar, teks tinta hangat.
- Satu warna aksi (teal laut) yang eksklusif untuk elemen interaktif.
- Kedalaman hanya lewat bayangan hangat tipis; tidak pernah bayangan ditambah garis.
- Satu-satunya gerak yang dirancang: pegas lembut saat ditekan, tanpa pantulan.
- Bilah navigasi bawah di ponsel, header atas di layar lebar.
- Hero halaman tanpa gambar sebagai komposisi dasar; foto hanya tambahan opsional di layar sedang ke atas.

## Colors

Palet hangat dan dingin yang disiplin: pasir dan tinta untuk dunia, satu teal untuk aksi, dua warna status kecil untuk makna.

### Primary

- **Teal Laut** (`action`): isian tombol aksi, tautan, kapsul "Lihat" pada kartu, ikon dan label tab aktif, kursor kolom isian. Satu-satunya warna aksi.
- **Teal Dalam** (`action-hover`): keadaan hover dan tekan untuk semua elemen teal. `action-deep` tercatat sebagai token tetapi belum dipakai di komponen yang disampel.
- **Embun Teal** (`action-50`, `action-100`, `action-200`): latar hover baris menu (50), serta seleksi teks dan bilah gulir (200). `action-100` tercatat tetapi belum terbukti dipakai di komponen yang disampel.

### Secondary

- **Tanah Liat** (`clay` di atas `clay-soft`): hanya untuk badge sumber Media Center. Berita dinas sendiri memakai badge netral (`ground-deep` dengan teks `ink`). Badge bukan elemen yang bisa ditekan, jadi tidak teal.
- **Hijau Status** (`ok` di atas `ok-soft`): hanya untuk penanda biaya gratis pada kartu layanan, selalu dengan ikon dan teks.

### Neutral

- **Pasir** (`ground`): latar halaman, dan sumur kolom cari dan baris tabel kepala.
- **Pasir Dalam** (`ground-deep`): sumur ikon, badge netral, hover item menu atas, bingkai foto hero.
- **Kartu Putih** (`card`): semua permukaan kartu, tombol senyap, bilah bawah, teks di atas teal.
- **Tinta Hangat** (`ink`): teks utama, strip situs resmi (latar), cincin fokus.
- **Tinta Redup** (`ink-muted`): teks sekunder, placeholder, ikon netral.
- **Tepi** (`edge`): batas kolom isian; dipilih agar memenuhi kontras 3:1.
- **Garis** (`line`): pemisah tipis di dalam kartu (daftar menu, sel tabel isi artikel).

### Named Rules

**The Teal Means Pressable Rule.** Teal hanya untuk yang bisa ditekan. Bila sebuah elemen tidak bisa ditekan, jangan beri teal; bila nonaktif, ia kehilangan warnanya. Uji: hapus semua teal dari halaman, dan yang tersisa tidak boleh ada yang terlihat seperti tombol.

**The Meaning Beyond Color Rule.** Sumber berita, status biaya, dan halaman aktif selalu dibedakan dengan ikon, teks, atau bentuk, tidak pernah dengan warna saja.

## Typography

**Display Font:** Nunito Sans (self-host lewat alias nunito, dengan cadangan sistem sans-serif)
**Body Font:** Nunito Sans, keluarga yang sama
**Label/Mono Font:** tidak ada; label memakai Nunito Sans tebal

**Character:** Sans humanis yang bulat dan ramah, dipakai di satu keluarga saja. Hierarki dibangun dari bobot (400, 600, 700, 800) dan ukuran, bukan dari pasangan font.

### Hierarchy

- **Display** (800, 1.875rem di ponsel naik ke 3rem di sm ke atas, 1.15): judul sapaan hero beranda.
- **Headline** (800, 1.875rem naik ke 2.25rem lalu 3rem di lg, 1.25): judul h1 kepala halaman daftar.
- **Title** (800, 1.125rem, 1.375): judul kartu layanan dan berita; judul bagian di artikel 1.5rem dan 1.25rem.
- **Body** (400, 1rem, relaxed): deskripsi dan teks kartu; isi artikel 1.0625rem dengan jarak baris 1.75, maksimum 70ch.
- **Label** (700, 0.75rem sampai 0.875rem): badge, tab bawah, strip situs resmi.

### Named Rules

**The Heavy Voice Rule.** Judul dan tombol memakai bobot 800; tautan dan label 700; teks biasa 400. Jangan menambah keluarga font kedua.

**The Balanced Heading Rule.** Judul h1 sampai h3 memakai text-wrap balance; angka dan waktu memakai angka tabular.

## Layout

Berbasis ponsel (360px) lalu naik. Kontainer maksimum 80rem (max-w-7xl) dengan padding 1rem di ponsel, 1.5rem di sm, 2rem di lg. Setiap bagian adalah kartu atau grid kartu di atas pasir.

Di bawah breakpoint lg, bilah tab bawah tetap (empat tujuan: Beranda, Layanan, Berita, Kontak, tinggi minimal 4.5rem, sudut atas 1.75rem, menghormati area aman perangkat) dan header hanya memuat merek serta tombol Menu 44px. Di lg ke atas, bilah bawah hilang dan header atas menampilkan menu dengan dropdown serta tombol senyap "Cari layanan". Widget aksesibilitas di ponsel diangkat ke atas bilah bawah lewat aturan di theme.css.

Hero beranda adalah komposisi halaman tanpa gambar: kartu sapaan dengan judul, kolom cari, dan satu tombol utama. Bila blok menyertakan foto, foto disembunyikan di bawah breakpoint sm (dan dimuat malas) supaya sapaan, kolom cari, dan petak aksi tetap berada di layar pertama ponsel; foto muncul sebagai kolom kanan di lg. Di lg tanpa foto, judul di kiri dan kolom cari di kanan sejajar tengah.

Ritme jarak mengikuti skala Tailwind: padding kartu 1.25rem (ponsel) sampai 2.5rem (hero di lg), jarak dalam kartu kecil 0.5rem sampai 1rem. Target sentuh minimal 44px (tombol aksi 3rem, senyap 2.75rem, tautan remah roti 2.75rem).

## Elevation & Depth

Kedalaman hanya dari bayangan hangat yang tipis, bernada cokelat (oklch 0.3 0.05 70), di atas pasir. Kartu diam sudah punya bayangan; hover menaikkannya satu tingkat.

### Shadow Vocabulary

- **Card** (`box-shadow: 0 6px 18px -8px oklch(0.3 0.05 70 / 0.22)`): kartu putih saat diam.
- **Lift** (`box-shadow: 0 14px 28px -12px oklch(0.3 0.05 70 / 0.32)`): kartu saat hover, dropdown menu.
- **Chip** (`box-shadow: 0 2px 8px -4px oklch(0.3 0.05 70 / 0.3)`): tombol senyap, tombol Menu, item menu aktif.
- **Bar** (`box-shadow: 0 -6px 18px -8px oklch(0.3 0.05 70 / 0.22)`): bilah tab bawah, bayangan ke atas.

### Named Rules

**The Shadow Or Border Rule.** Elevasi adalah bayangan, tidak pernah bayangan ditambah garis. Kartu tidak punya border. Satu-satunya batas yang tampak adalah batas kolom isian (fungsi, 3:1), bukan elevasi.

**The Focus Ring Exception.** Cincin fokus keyboard adalah garis tinta 3px dengan halo putih 2px; ia mengikuti bentuk elemen dan bukan elevasi. Pada kartu layanan, cincin dipasang pada seluruh kartu lewat tautan yang diperluas.

## Shapes

Bahasa bentuk besar dan membulat: kartu 22px (`card`), petak 20px (`tile`), kolom isian 16px (`control`), sumur ikon dan item menu 14px (`icon`). Tombol, badge, kapsul, dan tab aktif berbentuk pil penuh (999px). Bilah bawah membulat di sudut atas saja (1.75rem). Tidak ada sudut tajam, tidak ada garis tepi dekoratif.

## Components

### Buttons

- **Shape:** pil penuh (999px).
- **Aksi (`btn-action`):** isian teal, teks putih, tinggi minimal 3rem, padding horizontal 1.5rem, bobot 800. Satu per tampilan sebagai aksi utama (mis. "Cari layanan").
- **Hover / Tekan:** isian menjadi teal dalam; seluruh tombol dan kartu menyusut ke 0.97 saat ditekan dan mengendap kembali (220ms, kurva ease-soft), tanpa pantulan.
- **Senyap (`btn-quiet`):** putih, teks teal, bayangan chip yang naik ke bayangan card saat hover, tinggi minimal 2.75rem; untuk aksi kedua.
- **Tautan teks:** teal, bobot 800 atau 700, dengan panah yang bergeser 0.25rem saat hover.

### Cards / Containers

- **Corner Style:** 22px.
- **Background:** putih di atas pasir.
- **Shadow Strategy:** Card diam, Lift saat hover; tanpa border.
- **Internal Padding:** 1.25rem; kepala halaman 1.25rem sampai 2rem.
- **Kartu layanan:** judul tebal sebagai satu-satunya tautan (diperluas menutup kartu), deskripsi dua baris, dua kapsul info (jangka waktu, biaya), dan kapsul teal "Lihat" di sudut bawah. Kapsul itu penanda dekoratif, disembunyikan dari pembaca layar; tautan nyatanya adalah judul.

### Inputs / Fields

- **Style:** sumur pasir, border 1.5px `edge` (3:1), sudut 16px, tinggi 3rem, ikon cari di kiri.
- **Focus:** cincin fokus ganda global; kursor teal.
- **Hover:** batas berubah ke `ink-muted`.

### Navigation

- **Atas (lg ke atas):** item pil dengan hover `ground-deep`; halaman aktif putih dengan teks teal dan bayangan chip. Dropdown adalah kartu putih dengan bayangan Lift dan sudut 22px.
- **Bawah (ponsel):** empat tab; halaman aktif ditandai atribut aria-current, kapsul teal di belakang ikon, dan huruf lebih tebal, bukan warna saja.
- **Menu ponsel:** kartu putih dengan daftar lipat (details) dan pemisah `line`.
- **Strip situs resmi:** pita tinta di paling atas dengan teks pasir.

### Badge Sumber

Berita dinas: kapsul `ground-deep` dengan ikon Landmark. Media Center: kapsul `clay-soft` dengan ikon RadioTower. Selalu ikon plus teks.

### Prose

Isi HTML editor (`prose-moso`): lebar maksimum 70ch, tautan teal bergaris bawah, kutipan di sumur pasir, tabel dengan pemisah `line` dan bisa digulir menyamping di dalam kotaknya.

## Do's and Don'ts

### Do:

- **Do** pakai teal (`action`) hanya pada elemen yang bisa ditekan, dan satu tombol aksi utama per tampilan.
- **Do** beri kedalaman dengan salah satu dari empat bayangan hangat; naikkan satu tingkat saat hover.
- **Do** pakai satu keluarga Nunito Sans dengan bobot 400, 600, 700, 800.
- **Do** biarkan gerak hanya berupa pegas tekan (220ms, ease-soft) dan hormati prefers-reduced-motion serta "Hentikan animasi" di widget aksesibilitas.
- **Do** ambil warna dari token tema lewat class Tailwind; tanpa kode hex di komponen.
- **Do** bedakan makna dengan ikon dan teks selain warna; target sentuh minimal 44px.
- **Do** ambil nama kota, sapaan, dan tautan dari Pengaturan dan data situs.

### Don't:

- **Don't** menaruh border pada kartu yang sudah punya bayangan.
- **Don't** memberi teal pada elemen yang tidak bisa ditekan, termasuk badge, ikon dekoratif, atau elemen nonaktif.
- **Don't** menambah gerak masuk (fade, slide, pantulan) saat elemen muncul.
- **Don't** meniru TemaModern (rangka navy dan emas) atau gaya aplikasi konsumer yang riang (oranye, ilustrasi lucu, emoji).
- **Don't** memakai ikon glif atau emoji; ikon adalah SVG garis Lucide.
- **Don't** menjadikan foto syarat komposisi hero; hero harus utuh tanpa gambar.

## Known Limitations and Drift (tidak dikanonkan)

- **Tombol widget aksesibilitas.** Tombol mengambang di kanan bawah adalah kode inti (paket widget), bukan bagian tema. Saat menggulir, ia bisa menutupi kapsul "Lihat" dekoratif pada kartu layanan. Tema hanya mengatur warna aksennya dan mengangkatnya di atas bilah bawah di ponsel. Ini batasan yang diketahui, bukan aturan desain; perbaikannya ada di kode inti.
- **Teks resmi tertanam.** Strip situs resmi di header menanam teks "Situs resmi Pemerintah Kota Singkawang" di komponen, bertentangan dengan batasan bahwa nama kota hanya dari Pengaturan. Ini cacat build; jangan ditiru di permukaan baru.
- **Kapsul "Lihat"** adalah elemen teal yang tidak bisa ditekan sendiri (ia mewakili kartu yang bisa ditekan). Dicatat apa adanya, tidak dijadikan pola untuk elemen dekoratif lain.
