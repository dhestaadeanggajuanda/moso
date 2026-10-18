/**
 * Ikon garis buatan sendiri (stroke 2, sudut membulat) supaya widget tidak
 * bergantung pada pustaka ikon. Semua ikon dekoratif: diberi aria-hidden.
 */
const svg = (paths: string): string =>
    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${paths}</svg>`;

export const icons = {
    accessibility: svg(
        '<circle cx="12" cy="4.5" r="1.75"/><path d="M5 8.5 12 10l7-1.5"/><path d="M12 10v4.5"/><path d="m8.5 21 3.5-6.5 3.5 6.5"/>',
    ),
    close: svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>'),
    minus: svg('<path d="M5 12h14"/>'),
    plus: svg('<path d="M5 12h14"/><path d="M12 5v14"/>'),
    reset: svg('<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>'),
};
