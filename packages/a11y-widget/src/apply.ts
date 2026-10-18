import { ensureFont } from './fonts';
import { ensurePageStyles } from './pageStyles';
import type { Preferences } from './preferences';

function setAttribute(
    element: Element,
    name: string,
    value: string | null,
): void {
    if (value === null) {
        element.removeAttribute(name);
    } else {
        element.setAttribute(name, value);
    }
}

/**
 * Terapkan preferensi ke halaman lewat atribut data-a11y-* di <html>.
 * Gaya halaman dan font baru dipasang saat dibutuhkan.
 */
export function applyPreferences(
    doc: Document,
    preferences: Preferences,
): void {
    ensurePageStyles(doc);
    ensureFont(doc, preferences.font);

    const html = doc.documentElement;
    setAttribute(
        html,
        'data-a11y-text',
        preferences.textScale === 100 ? null : String(preferences.textScale),
    );
    setAttribute(
        html,
        'data-a11y-spacing',
        preferences.textSpacing ? '' : null,
    );
    setAttribute(
        html,
        'data-a11y-contrast',
        preferences.contrast === 'normal' ? null : preferences.contrast,
    );
    setAttribute(
        html,
        'data-a11y-links',
        preferences.highlightLinks ? '' : null,
    );
    setAttribute(
        html,
        'data-a11y-font',
        preferences.font === 'default' ? null : preferences.font,
    );
    setAttribute(html, 'data-a11y-cursor', preferences.bigCursor ? '' : null);
    setAttribute(
        html,
        'data-a11y-motion',
        preferences.stopMotion ? 'off' : null,
    );
    setAttribute(
        html,
        'data-a11y-images',
        preferences.hideImages ? 'off' : null,
    );
}

/** Hapus semua atribut data-a11y-* dari <html> sehingga halaman tampil seperti semula. */
export function clearPreferences(doc: Document): void {
    const html = doc.documentElement;

    [...html.attributes]
        .filter((attribute) => attribute.name.startsWith('data-a11y-'))
        .forEach((attribute) => html.removeAttribute(attribute.name));
}
