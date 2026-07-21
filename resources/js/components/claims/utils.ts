export const EMPTY_VALUE = '-';

export const currency = (value: string | number | null) =>
    value === null || value === '' ? EMPTY_VALUE : Number(value).toLocaleString(undefined, { style: 'currency', currency: 'USD' });

export const date = (value: string | null) => (value ? new Date(`${value}T00:00:00`).toLocaleDateString() : EMPTY_VALUE);

export const dateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : EMPTY_VALUE);

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

export const statusClass: Record<string, string> = {
    draft: 'border-gray-300 bg-gray-100 text-gray-800',
    appeal: 'border-purple-300 bg-purple-100 text-purple-800',
    rebilled: 'border-blue-300 bg-blue-100 text-blue-800',
    called_pt_for_info: 'border-orange-300 bg-orange-100 text-orange-800',
    corrected: 'border-blue-300 bg-blue-100 text-blue-800',
    paid: 'border-green-300 bg-green-100 text-green-800',
    patient_balance: 'border-pink-300 bg-pink-100 text-pink-800',
    adjustment: 'border-orange-300 bg-orange-100 text-orange-800',
    other: 'border-gray-300 bg-gray-100 text-gray-800',
    project: 'border-blue-300 bg-blue-100 text-blue-800',
    pending: 'border-yellow-300 bg-yellow-100 text-yellow-800',
    void: 'border-gray-400 bg-gray-200 text-gray-600',
    historical_posted_payments: 'border-teal-300 bg-teal-50 text-teal-800',
};

export const statusRowClass: Record<string, string> = {
    draft: 'bg-gray-100',
    appeal: 'bg-purple-100',
    rebilled: 'bg-blue-100',
    called_pt_for_info: 'bg-orange-100',
    corrected: 'bg-blue-100',
    paid: 'bg-green-100',
    patient_balance: 'bg-pink-100',
    adjustment: 'bg-orange-100',
    other: 'bg-gray-100',
    project: 'bg-blue-100',
    pending: 'bg-yellow-100',
    void: 'bg-gray-200',
    historical_posted_payments: 'bg-teal-100',
};
