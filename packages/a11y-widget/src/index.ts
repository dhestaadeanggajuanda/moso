/**
 * @moso/a11y-widget: menu aksesibilitas WCAG 2.2 yang bisa dipasang di aplikasi apa pun.
 *
 * Widget ini pelengkap; situs tetap wajib memenuhi WCAG 2.2 AA sendiri.
 */
import { A11yWidget } from './A11yWidget';

export { A11yWidget };
export {
    DEFAULT_PREFERENCES,
    DEFAULT_STORAGE_KEY,
    TEXT_SCALES,
} from './preferences';
export type {
    ContrastMode,
    FontMode,
    Preferences,
    TextScale,
} from './preferences';

export const TAG_NAME = 'a11y-widget';

export type MountOptions = {
    lang?: 'id' | 'en';
    position?: 'bottom-right' | 'bottom-left';
    storageKey?: string;
    /** Tempat menempelkan widget; bawaan document.body (di luar pohon aplikasi, agar filter halaman tidak mengenainya). */
    target?: HTMLElement;
};

/** Daftarkan custom element sekali. */
export function define(): void {
    if (!customElements.get(TAG_NAME)) {
        customElements.define(TAG_NAME, A11yWidget);
    }
}

/**
 * Pasang satu widget di halaman. Aman dipanggil berulang (mis. setiap navigasi
 * SPA): widget yang sudah ada dipakai kembali.
 */
export function mount(options: MountOptions = {}): A11yWidget {
    define();

    const existing = document.querySelector<A11yWidget>(TAG_NAME);

    if (existing) {
        return existing;
    }

    const widget = document.createElement(TAG_NAME) as A11yWidget;

    if (options.lang) {
        widget.setAttribute('lang', options.lang);
    }

    if (options.position) {
        widget.setAttribute('position', options.position);
    }

    if (options.storageKey) {
        widget.setAttribute('storage-key', options.storageKey);
    }

    (options.target ?? document.body).append(widget);

    return widget;
}

/**
 * Lepas widget, mis. saat SPA pindah ke area tanpa widget. Halaman kembali ke
 * tampilan asli; preferensi tersimpan dipakai lagi pada mount berikutnya.
 */
export function unmount(): void {
    document.querySelector(TAG_NAME)?.remove();
}
