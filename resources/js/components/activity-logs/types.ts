import type { PaginationLink } from '@/components/pagination';

export interface ActivityFilters {
    search: string;
    role: string;
    worked_from: string;
    worked_to: string;
}

export interface StatusSummaryItem {
    status: string;
    label: string;
    color: string | null;
    count: number;
    amount: number;
}

export interface UserMetric {
    user_id: number;
    name: string;
    email: string | null;
    is_admin: boolean;
    total_lines: number;
    worked_lines: number;
    closed_lines: number;
    total_balance: number;
    closed_balance: number;
}

export interface WorkedLine {
    id: number;
    claim_id: number;
    claim_number: string;
    patient_name: string | null;
    cpt_code: string | null;
    status: string;
    status_label: string;
    status_color: string | null;
    date_of_service: string | null;
    worked_at: string | null;
    charges: number;
    paid: number;
    balance: number;
    denial_reason: string | null;
    assigned_to: { id: number; name: string; email: string } | null;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export const formatNumber = (value: number) => value.toLocaleString('en-US');

export const formatCurrency = (value: number) =>
    value.toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
