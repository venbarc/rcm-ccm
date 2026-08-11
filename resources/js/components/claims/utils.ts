import { formatBusinessDateTime } from '@/lib/date-time';
import type { CSSProperties } from 'react';

export const EMPTY_VALUE = '-';

export const currency = (value: string | number | null) =>
    value === null || value === '' ? EMPTY_VALUE : Number(value).toLocaleString(undefined, { style: 'currency', currency: 'USD' });

export const date = (value: string | null) => (value ? new Date(`${value}T00:00:00`).toLocaleDateString() : EMPTY_VALUE);

export const longDate = (value: string | null) =>
    value
        ? new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : EMPTY_VALUE;

export const dateTime = (value: string | null) => formatBusinessDateTime(value, EMPTY_VALUE);

export const serviceDateRange = (start: string | null, end: string | null) => {
    if (!start && !end) {
        return EMPTY_VALUE;
    }

    if (!start || !end || start === end) {
        return date(start ?? end);
    }

    return `${date(start)} - ${date(end)}`;
};

export const statusLabel = (value: string | null) => (value || 'draft').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export const formatServiceMonth = (value: string) => {
    if (!/^\d{4}-\d{2}$/.test(value)) {
        return value;
    }

    return new Date(`${value}-01T00:00:00`).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
};

export const lineProcedureCode = (procedureCode: string | null, cptCode: string | null) => procedureCode || cptCode || EMPTY_VALUE;

export const DEFAULT_WORK_STATUS_COLOR = '#F3F4F6';

const normalizedWorkStatusColor = (color: string | null | undefined) => (color && /^#[0-9A-F]{6}$/i.test(color) ? color : DEFAULT_WORK_STATUS_COLOR);

export const workStatusForegroundColor = (color: string | null | undefined) => {
    const normalized = normalizedWorkStatusColor(color);
    const red = Number.parseInt(normalized.slice(1, 3), 16);
    const green = Number.parseInt(normalized.slice(3, 5), 16);
    const blue = Number.parseInt(normalized.slice(5, 7), 16);
    const luminance = (0.2126 * red + 0.7152 * green + 0.0722 * blue) / 255;

    return luminance > 0.55 ? '#0F172A' : '#FFFFFF';
};

export const workStatusBackgroundStyle = (color: string | null | undefined): CSSProperties => ({
    backgroundColor: normalizedWorkStatusColor(color),
    color: workStatusForegroundColor(color),
});

export const workStatusBadgeStyle = (color: string | null | undefined): CSSProperties => ({
    ...workStatusBackgroundStyle(color),
    borderColor: '#94A3B8',
});
