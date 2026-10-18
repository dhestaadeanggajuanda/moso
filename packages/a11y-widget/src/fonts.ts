/**
 * Font mudah dibaca (Atkinson Hyperlegible) dan font disleksia (OpenDyslexic),
 * dari paket Fontsource (lisensi SIL OFL 1.1). Berkas ikut dibundel aplikasi
 * induk atau build widget, tanpa CDN, dan baru dimuat saat opsinya dipakai.
 * OpenDyslexic hanya memuat bobot reguler karena berkasnya besar (sekitar 116 KB).
 */
import atkinson400 from '@fontsource/atkinson-hyperlegible/files/atkinson-hyperlegible-latin-400-normal.woff2?url';
import atkinson700 from '@fontsource/atkinson-hyperlegible/files/atkinson-hyperlegible-latin-700-normal.woff2?url';
import openDyslexic400 from '@fontsource/opendyslexic/files/opendyslexic-latin-400-normal.woff2?url';
import type { FontMode } from './preferences';

const FONT_STYLE_ID = 'a11y-widget-fonts';

const FACES: Record<Exclude<FontMode, 'default'>, string> = {
    readable: `
@font-face { font-family: "Atkinson Hyperlegible"; font-style: normal; font-weight: 400; font-display: swap; src: url("${atkinson400}") format("woff2"); }
@font-face { font-family: "Atkinson Hyperlegible"; font-style: normal; font-weight: 700; font-display: swap; src: url("${atkinson700}") format("woff2"); }`,
    dyslexic: `
@font-face { font-family: "OpenDyslexic"; font-style: normal; font-weight: 400; font-display: swap; src: url("${openDyslexic400}") format("woff2"); }`,
};

/**
 * Pasang @font-face sekali per jenis font. @font-face harus di dokumen, bukan
 * di Shadow DOM, karena font dipakai oleh isi halaman.
 */
export function ensureFont(doc: Document, font: FontMode): void {
    if (font === 'default') {
        return;
    }

    const id = `${FONT_STYLE_ID}-${font}`;

    if (doc.getElementById(id)) {
        return;
    }

    const style = doc.createElement('style');
    style.id = id;
    style.textContent = FACES[font];
    doc.head.append(style);
}
