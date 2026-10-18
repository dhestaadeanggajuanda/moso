# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Warga yang mencari layanan dinas.** Ingin tahu persyaratan, prosedur, biaya, dan jangka waktu sebuah layanan sebelum datang ke kantor. Sering membuka situs dari ponsel, kadang dengan koneksi terbatas.
- **Pembaca berita dan agenda.** Warga dan jurnalis yang mengikuti kegiatan dinas, baik berita dinas sendiri maupun berita lintas sektor dari Media Center Kota Singkawang, serta jadwal kegiatan terdekat.
- **Warga yang butuh kontak atau ingin mengadu.** Mencari alamat, nomor telepon, email, dan saluran pengaduan.

Pengelola konten (Super Admin, Approver, Editor, Kontributor) memakai panel admin terpisah; dokumen ini berfokus pada situs publik.

## Product Purpose

Portal berita dan layanan resmi untuk dinas di lingkungan Pemerintah Kota Singkawang. Satu kode yang sama dipasang untuk banyak dinas. Situs harus membuat informasi layanan dan berita mudah ditemukan, dipercaya sebagai sumber resmi, dan memenuhi kewajiban keterbukaan informasi serta standar pelayanan publik.

Berhasil bila warga menemukan layanan beserta persyaratannya tanpa harus menelepon, membaca berita terbaru dinas dan kota di satu tempat, dan tahu cara menghubungi atau mengadu ke dinas.

## Positioning

Situs resmi milik dinas, bukan portal berita swasta. Berita dinas sendiri digabung dengan berita lintas sektor dari Media Center Kota (ditandai jelas asalnya), dan modul kepatuhan (PPID, Standar Pelayanan, E-SAKIP) tersedia dari situs yang sama.

## Operating Context

- Dipasang per dinas. Nama situs, tagline, logo, kontak, media sosial, menu, dan kata kunci Media Center diatur lewat Pengaturan situs dan Page Builder, bukan ditanam di tema.
- Halaman disusun pengelola lewat Page Builder dari blok: banner utama, grid berita (lokal + Media Center), agenda kegiatan, dan teks. Tema harus tetap rapi untuk kombinasi dan urutan blok apa pun.
- Isi berita ditulis pegawai dinas dengan kualitas foto beragam; sebagian berita tidak punya gambar.

## Capabilities and Constraints

- Laravel 13 + Inertia 3 + Vue 3, Tailwind CSS 4; halaman publik dirender server (SSR) untuk SEO.
- Pluggable Themes: setiap tema hidup di `resources/js/Themes/{Tema}`; hanya tema yang terdaftar di `VITE_THEMES` yang dikompilasi.
- Komponen tema hanya merender props (tanpa memanggil API); tim magang hanya boleh mengubah template dan class Tailwind.
- Teks dari Media Center selalu teks polos. Berita lokal tanpa gambar harus tetap tampil baik.
- Netral terhadap dinas: tema tidak boleh mengandung nama, warna khas, atau logo dinas tertentu.

## Brand Commitments

- Logo resmi Pemerintah Kota Singkawang dan logo dinas akan disediakan pengguna; tema harus menyediakan tempatnya (belum tersedia di repositori).
- Bahasa Indonesia baku yang ramah dan mudah dipahami warga.
- Arah visual tema publik dipilih pengguna: standar portal pemerintah daerah yang dipoles agar modern dan terkini, dikerjakan lurus tanpa gaya eksperimental. Tolok ukur kualitas: jakarta.go.id.

## Evidence on Hand

- Berita nyata dari Media Center Kota Singkawang lewat WordPress REST API (judul, ringkasan, gambar, tanggal).
- Belum ada logo, foto resmi, testimoni, atau statistik layanan di repositori; jangan dibuat-buat.

## Product Principles

1. **Layanan dulu.** Informasi yang membantu warga bertindak (persyaratan, kontak, pengaduan) harus selalu dekat dan mudah dijangkau.
2. **Sumber jelas.** Setiap berita menunjukkan asalnya (dinas atau Media Center Kota) dan tanggalnya.
3. **Satu tema untuk semua dinas.** Identitas berasal dari pengaturan, bukan dari kode tema.
4. **Ringan dan tangguh.** Tetap terbaca di ponsel murah dan koneksi lambat; tetap utuh bila sebagian data atau layanan luar gagal.

## Accessibility & Inclusion

WCAG 2.2 AA wajib: HTML semantik, skip link, kontras AA, fokus keyboard terlihat, target sentuh minimal 24px, dan menghormati `prefers-reduced-motion`. Widget aksesibilitas buatan sendiri menyusul sebagai pelengkap, bukan pengganti.
