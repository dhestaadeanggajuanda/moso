/**
 * Gaya yang diterapkan ke halaman induk lewat atribut data-a11y-* di <html>.
 * Elemen widget sendiri (`a11y-widget`) dikecualikan agar panel tetap terbaca
 * dan posisinya tidak rusak oleh filter. `!important` diperlukan untuk menimpa
 * gaya situs; nilai warna di sini milik widget, bukan token tema situs.
 */

export const PAGE_STYLE_ID = 'a11y-widget-page-styles';

const BIG_CURSOR = `url("data:image/svg+xml,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><path d="M8 4l30 18-13 3 8 16-6 3-8-16-11 9z" fill="#111" stroke="#fff" stroke-width="3" stroke-linejoin="round"/></svg>',
)}") 8 4, auto`;

export const PAGE_STYLES = `
html[data-a11y-text="115"] { font-size: 115% !important; }
html[data-a11y-text="130"] { font-size: 130% !important; }
html[data-a11y-text="150"] { font-size: 150% !important; }

html[data-a11y-spacing] body *:not(a11y-widget) {
    line-height: 1.5 !important;
    letter-spacing: 0.12em !important;
    word-spacing: 0.16em !important;
}
html[data-a11y-spacing] body p:not(a11y-widget) { margin-bottom: 2em !important; }

html[data-a11y-contrast="high"] body > *:not(a11y-widget) { filter: contrast(1.5) saturate(1.2); }
html[data-a11y-contrast="grayscale"] body > *:not(a11y-widget) { filter: grayscale(1); }
html[data-a11y-contrast="invert"] body > *:not(a11y-widget) { filter: invert(1) hue-rotate(180deg); }
html[data-a11y-contrast="invert"] body > *:not(a11y-widget) :is(img, picture, video) { filter: invert(1) hue-rotate(180deg); }

html[data-a11y-links] body a:not(a11y-widget) {
    text-decoration-line: underline !important;
    text-decoration-thickness: 2px !important;
    text-underline-offset: 3px !important;
    background-color: #fff3a3 !important;
    color: #111 !important;
    outline: 2px solid #111 !important;
    outline-offset: 1px !important;
}

html[data-a11y-font="readable"] body *:not(a11y-widget) {
    font-family: "Atkinson Hyperlegible", Verdana, Tahoma, sans-serif !important;
}
html[data-a11y-font="dyslexic"] body *:not(a11y-widget) {
    font-family: "OpenDyslexic", "Atkinson Hyperlegible", Verdana, sans-serif !important;
}

html[data-a11y-cursor],
html[data-a11y-cursor] body *:not(a11y-widget) { cursor: ${BIG_CURSOR} !important; }

html[data-a11y-motion="off"] *,
html[data-a11y-motion="off"] *::before,
html[data-a11y-motion="off"] *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
}

html[data-a11y-images="off"] body :is(img, picture, video, [role="img"]):not(a11y-widget) {
    opacity: 0 !important;
}

[data-a11y-reading] {
    outline: 3px solid #f59e0b !important;
    outline-offset: 3px !important;
    background-color: #fff7d6 !important;
    color: #111 !important;
}
`;

export function ensurePageStyles(doc: Document): void {
    if (doc.getElementById(PAGE_STYLE_ID)) {
        return;
    }

    const style = doc.createElement('style');
    style.id = PAGE_STYLE_ID;
    style.textContent = PAGE_STYLES;
    doc.head.append(style);
}
