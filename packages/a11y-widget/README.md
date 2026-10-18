# @moso/a11y-widget

Menu aksesibilitas WCAG 2.2 sebagai Web Component (`<a11y-widget>`, Shadow DOM, TypeScript tanpa framework). Bisa dipasang di aplikasi apa pun dengan satu tag `<script>`, atau diimpor lewat bundler.

Widget ini **pelengkap**: situs tetap wajib memenuhi WCAG 2.2 AA sendiri. Panduan lengkap untuk moso ada di [`docs/ACCESSIBILITY.md`](../../docs/ACCESSIBILITY.md).

## Fitur

Ukuran teks (100–150%), spasi teks sesuai WCAG 1.4.12, font mudah dibaca (Atkinson Hyperlegible) atau ramah disleksia (OpenDyslexic), sorot tautan, kontras tinggi, skala abu-abu, warna terbalik, sembunyikan gambar, hentikan animasi (WCAG 2.2.2), kursor besar, panduan baca, bacakan teks (Web Speech API, `id-ID` atau `en-US`), dan atur ulang.

Preferensi disimpan di `localStorage` perangkat pengunjung, tanpa cookie, tanpa permintaan ke server. Font di-self-host dan baru diunduh saat dipilih.

## Pemasangan

### Satu tag `<script>` (tanpa bundler)

```bash
npm run build:widget
```

Salin isi `packages/a11y-widget/dist` (`a11y-widget.iife.js` dan folder `fonts/`, termasuk berkas lisensi fontnya) ke folder publik aplikasi, lalu:

```html
<script
    src="/vendor/moso/a11y-widget.iife.js"
    data-lang="id"
    data-position="bottom-right"
    defer
></script>
```

| Atribut            | Nilai                                  | Bawaan                |
| ------------------ | -------------------------------------- | --------------------- |
| `data-lang`        | `id` atau `en`                         | `id`                  |
| `data-position`    | `bottom-right` atau `bottom-left`      | `bottom-right`        |
| `data-storage-key` | kunci `localStorage`                   | `moso-a11y-widget:v1` |
| `data-auto-mount`  | `false` untuk memasang sendiri via API | dipasang otomatis     |

API yang sama tersedia di `window.MosoA11yWidget` (`mount`, `unmount`, `define`, `A11yWidget`).

Pakai `data-storage-key` berbeda bila satu domain memuat beberapa aplikasi yang tidak boleh berbagi pengaturan.

### Lewat bundler (workspace npm)

```ts
import { mount, unmount } from '@moso/a11y-widget';

mount({ lang: 'id', position: 'bottom-right' });
```

Font diimpor dengan `?url`, jadi bundler aplikasi (Vite) menyalinnya sebagai aset terpisah. Untuk SPA, panggil `mount()` di setiap navigasi (aman dipanggil berulang) dan `unmount()` saat pindah ke area tanpa widget. Contoh integrasi: `resources/js/lib/a11yWidget.ts` di moso.

### Sebagai elemen HTML

```ts
import { define } from '@moso/a11y-widget';

define();
```

```html
<a11y-widget lang="en" position="bottom-left"></a11y-widget>
```

## API

| Ekspor                                                      | Keterangan                                                                                                   |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `mount(options?)`                                           | Pasang satu widget di `document.body` (atau `options.target`); mengembalikan widget yang sudah ada bila ada. |
| `unmount()`                                                 | Lepas widget. Halaman kembali ke tampilan asli; preferensi tersimpan dipakai lagi saat `mount()` berikutnya. |
| `define()`                                                  | Daftarkan custom element `a11y-widget` (sekali).                                                             |
| `A11yWidget`                                                | Kelas elemen; `currentPreferences` berisi salinan preferensi yang berlaku.                                   |
| `DEFAULT_PREFERENCES`, `DEFAULT_STORAGE_KEY`, `TEXT_SCALES` | Konstanta preferensi.                                                                                        |

`options`: `lang` (`'id' | 'en'`), `position` (`'bottom-right' | 'bottom-left'`), `storageKey`, `target`.

### Event

Widget mengirim `a11y-change` (bubbles, composed) setiap kali pengaturan berubah, dengan `detail` berisi preferensi:

```ts
document.addEventListener('a11y-change', (event) => {
    const { stopMotion } = (event as CustomEvent).detail;
});
```

### Atribut di `<html>`

Pengaturan diterapkan lewat atribut di `<html>`, sehingga aplikasi bisa ikut menyesuaikan, misalnya menghentikan karusel JavaScript saat `data-a11y-motion="off"`:

| Atribut              | Nilai                         |
| -------------------- | ----------------------------- |
| `data-a11y-text`     | `115`, `130`, `150`           |
| `data-a11y-spacing`  | ada / tidak ada               |
| `data-a11y-contrast` | `high`, `grayscale`, `invert` |
| `data-a11y-links`    | ada / tidak ada               |
| `data-a11y-font`     | `readable`, `dyslexic`        |
| `data-a11y-cursor`   | ada / tidak ada               |
| `data-a11y-motion`   | `off`                         |
| `data-a11y-images`   | `off`                         |

### Warna

Warna aksen mengikuti variabel CSS halaman:

```css
html {
    --a11y-accent: #1e3a8a; /* latar tombol dan judul panel */
    --a11y-accent-text: #ffffff; /* teks di atas aksen, kontras ≥ 4.5:1 */
}
```

## CSP

Tanpa inline script dan `eval`. Butuh `style-src 'unsafe-inline'` (gaya di `<head>` dan Shadow DOM), `font-src 'self'`, dan `img-src data:` (kursor besar).

## Pengembangan

```bash
npm run test:widget   # Vitest + happy-dom
npm run build:widget  # dist/a11y-widget.js (ESM) dan dist/a11y-widget.iife.js
```

```
src/
  index.ts          API publik (mount, unmount, define)
  standalone.ts     entri IIFE, membaca data-* dari tag <script>
  A11yWidget.ts     custom element: panel, kontrol, pengumuman
  preferences.ts    tipe, nilai bawaan, simpan/muat (try/catch)
  apply.ts          menerapkan dan menghapus atribut data-a11y-* di <html>
  pageStyles.ts     CSS yang disisipkan ke halaman
  widgetStyles.ts   CSS di dalam Shadow DOM
  fonts.ts          @font-face Atkinson Hyperlegible dan OpenDyslexic
  features/         speech.ts (Web Speech API), readingGuide.ts
  i18n/             id.ts, en.ts
```

Butuh browser modern dengan Custom Elements v1 dan Shadow DOM (Chrome, Edge, Firefox, Safari versi terkini).

## Lisensi

Widget: MIT. Font Atkinson Hyperlegible dan OpenDyslexic (lewat Fontsource): SIL Open Font License 1.1.
