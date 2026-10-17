const TIME_ZONE = 'Asia/Pontianak';

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: TIME_ZONE,
});

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeZone: TIME_ZONE,
});

export function formatDateTime(value: string | null | undefined): string {
    return value ? dateTimeFormatter.format(new Date(value)) : '-';
}

export function formatDate(value: string | null | undefined): string {
    return value ? dateFormatter.format(new Date(value)) : '-';
}

const sizeFormatter = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 1,
});

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${sizeFormatter.format(value)} ${units[unit]}`;
}
