const FALLBACK_BUSINESS_TIME_ZONE = 'America/Los_Angeles';

export const businessTimeZone =
    (typeof document !== 'undefined' ? document.querySelector<HTMLMetaElement>('meta[name="app-business-timezone"]')?.content : undefined) ||
    FALLBACK_BUSINESS_TIME_ZONE;

const validDate = (value: string | Date): Date | null => {
    const parsed = value instanceof Date ? value : new Date(value);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

export function formatBusinessDateTime(value: string | Date | null | undefined, fallback = '-'): string {
    if (!value) return fallback;

    const parsed = validDate(value);
    if (!parsed) return fallback;

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'short',
        timeStyle: 'medium',
        timeZone: businessTimeZone,
    }).format(parsed);
}

export function formatBusinessDate(value: string | Date | null | undefined, fallback = '-'): string {
    if (!value) return fallback;

    const parsed = validDate(value);
    if (!parsed) return fallback;

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'short',
        timeZone: businessTimeZone,
    }).format(parsed);
}

export function businessTodayIso(): string {
    const parts = new Intl.DateTimeFormat('en-US', {
        day: '2-digit',
        month: '2-digit',
        timeZone: businessTimeZone,
        year: 'numeric',
    }).formatToParts(new Date());
    const value = Object.fromEntries(parts.map((part) => [part.type, part.value]));

    return `${value.year}-${value.month}-${value.day}`;
}
