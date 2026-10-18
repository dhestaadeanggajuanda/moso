/**
 * Format tanggal dan jam untuk situs publik, selalu dalam WIB (Asia/Pontianak)
 * supaya hasil render server (SSR) dan browser sama.
 */
const TIME_ZONE = 'Asia/Pontianak';
const LOCALE = 'id-ID';

function toDate(iso: string | null | undefined): Date | null {
    if (!iso) {
        return null;
    }

    const date = new Date(iso);

    return Number.isNaN(date.getTime()) ? null : date;
}

/** "21 Oktober 2026" */
export function formatDate(iso: string | null | undefined): string {
    const date = toDate(iso);

    return date
        ? new Intl.DateTimeFormat(LOCALE, {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
              timeZone: TIME_ZONE,
          }).format(date)
        : '';
}

/** "21 Okt 2026" */
export function formatShortDate(iso: string | null | undefined): string {
    const date = toDate(iso);

    return date
        ? new Intl.DateTimeFormat(LOCALE, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
              timeZone: TIME_ZONE,
          }).format(date)
        : '';
}

/** Bagian tanggal untuk ubin kalender: { day: "21", month: "Okt", weekday: "Rabu", weekdayShort: "Rab" } */
export function dateParts(iso: string | null | undefined): {
    day: string;
    month: string;
    weekday: string;
    weekdayShort: string;
} {
    const date = toDate(iso);

    if (!date) {
        return { day: '', month: '', weekday: '', weekdayShort: '' };
    }

    const part = (options: Intl.DateTimeFormatOptions) =>
        new Intl.DateTimeFormat(LOCALE, {
            ...options,
            timeZone: TIME_ZONE,
        }).format(date);

    return {
        day: part({ day: 'numeric' }),
        month: part({ month: 'short' }).replace('.', ''),
        weekday: part({ weekday: 'long' }),
        weekdayShort: part({ weekday: 'short' }).replace('.', ''),
    };
}

/** "08.00–12.00 WIB" atau "08.00 WIB" */
export function formatTimeRange(
    startIso: string | null | undefined,
    endIso?: string | null,
): string {
    const start = toDate(startIso);

    if (!start) {
        return '';
    }

    const time = (date: Date) =>
        new Intl.DateTimeFormat(LOCALE, {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: TIME_ZONE,
        }).format(date);

    const end = toDate(endIso);
    const sameDay =
        end !== null &&
        formatShortDate(end.toISOString()) ===
            formatShortDate(start.toISOString());

    return end && sameDay
        ? `${time(start)}–${time(end)} WIB`
        : `${time(start)} WIB`;
}

/**
 * Path sebuah URL internal (relatif atau satu origin dengan `appUrl`), untuk
 * menandai menu yang aktif. Tautan ke situs lain selalu null.
 */
export function pathOf(
    href: string | null | undefined,
    appUrl: string,
): string | null {
    if (!href) {
        return null;
    }

    try {
        const base = new URL(appUrl);
        const url = new URL(href, base);

        return url.origin === base.origin
            ? url.pathname.replace(/\/+$/, '')
            : null;
    } catch {
        return null;
    }
}
