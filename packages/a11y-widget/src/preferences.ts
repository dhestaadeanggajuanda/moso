/**
 * Preferensi pengunjung dan penyimpanannya. localStorage bisa tidak tersedia
 * (mode privat, kuota penuh, diblokir), jadi semua akses dibungkus try/catch
 * dan widget tetap bekerja tanpa penyimpanan.
 */

export const TEXT_SCALES = [100, 115, 130, 150] as const;

export type TextScale = (typeof TEXT_SCALES)[number];
export type ContrastMode = 'normal' | 'high' | 'grayscale' | 'invert';
export type FontMode = 'default' | 'readable' | 'dyslexic';

export type Preferences = {
    textScale: TextScale;
    /** Spasi teks sesuai WCAG 1.4.12 (tinggi baris, huruf, kata, paragraf). */
    textSpacing: boolean;
    contrast: ContrastMode;
    highlightLinks: boolean;
    font: FontMode;
    bigCursor: boolean;
    readingGuide: boolean;
    /** Menghentikan animasi dan transisi (WCAG 2.2.2). */
    stopMotion: boolean;
    hideImages: boolean;
    /** Membacakan teks yang diklik dengan Web Speech API. */
    speech: boolean;
};

export const DEFAULT_PREFERENCES: Readonly<Preferences> = Object.freeze({
    textScale: 100,
    textSpacing: false,
    contrast: 'normal',
    highlightLinks: false,
    font: 'default',
    bigCursor: false,
    readingGuide: false,
    stopMotion: false,
    hideImages: false,
    speech: false,
});

export const DEFAULT_STORAGE_KEY = 'moso-a11y-widget:v1';

const CONTRASTS: readonly ContrastMode[] = [
    'normal',
    'high',
    'grayscale',
    'invert',
];
const FONTS: readonly FontMode[] = ['default', 'readable', 'dyslexic'];

/**
 * Ambil nilai yang dikenal saja dari data mentah (mis. JSON lama atau rusak).
 */
export function sanitize(raw: unknown): Preferences {
    const source =
        raw !== null && typeof raw === 'object'
            ? (raw as Record<string, unknown>)
            : {};
    const flag = (key: keyof Preferences): boolean =>
        typeof source[key] === 'boolean'
            ? (source[key] as boolean)
            : (DEFAULT_PREFERENCES[key] as boolean);

    return {
        textScale: TEXT_SCALES.includes(source.textScale as TextScale)
            ? (source.textScale as TextScale)
            : DEFAULT_PREFERENCES.textScale,
        textSpacing: flag('textSpacing'),
        contrast: CONTRASTS.includes(source.contrast as ContrastMode)
            ? (source.contrast as ContrastMode)
            : DEFAULT_PREFERENCES.contrast,
        highlightLinks: flag('highlightLinks'),
        font: FONTS.includes(source.font as FontMode)
            ? (source.font as FontMode)
            : DEFAULT_PREFERENCES.font,
        bigCursor: flag('bigCursor'),
        readingGuide: flag('readingGuide'),
        stopMotion: flag('stopMotion'),
        hideImages: flag('hideImages'),
        speech: flag('speech'),
    };
}

export function loadPreferences(storageKey: string): Preferences {
    try {
        const stored = window.localStorage.getItem(storageKey);

        return stored
            ? sanitize(JSON.parse(stored))
            : { ...DEFAULT_PREFERENCES };
    } catch {
        return { ...DEFAULT_PREFERENCES };
    }
}

export function savePreferences(
    storageKey: string,
    preferences: Preferences,
): void {
    try {
        if (isDefault(preferences)) {
            window.localStorage.removeItem(storageKey);
        } else {
            window.localStorage.setItem(
                storageKey,
                JSON.stringify(preferences),
            );
        }
    } catch {
        // Tanpa penyimpanan, preferensi tetap berlaku sampai halaman ditutup.
    }
}

export function isDefault(preferences: Preferences): boolean {
    return (Object.keys(DEFAULT_PREFERENCES) as (keyof Preferences)[]).every(
        (key) => preferences[key] === DEFAULT_PREFERENCES[key],
    );
}

/** Ukuran teks berikutnya (+1) atau sebelumnya (-1), tidak melewati batas. */
export function stepTextScale(
    current: TextScale,
    direction: 1 | -1,
): TextScale {
    const index = TEXT_SCALES.indexOf(current) + direction;

    return TEXT_SCALES[Math.min(Math.max(index, 0), TEXT_SCALES.length - 1)];
}
