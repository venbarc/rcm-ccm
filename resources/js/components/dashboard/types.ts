export interface DashboardFilters {
    preset: string;
    start: string | null;
    end: string | null;
    label: string;
    presetLabel: string;
}

export interface WorkSummary {
    totalCount: number;
    totalAmount: number;
    workedCount: number;
    workedAmount: number;
    remainingCount: number;
    remainingAmount: number;
    paidCount: number;
    paidAmount: number;
    workedProgress: number;
}

export interface ClaimByStatus {
    status: string;
    label: string;
    count: number;
    amount: number;
}

export interface RecentClaim {
    id: number;
    claim_number: string;
    patient_name: string | null;
    lines_count: number;
    total_balance: number;
    view_url: string;
}

export interface PayerBalance {
    payer: string;
    count: number;
    amount: number;
}

export const formatCount = (value: number) => value.toLocaleString('en-US');

export const formatCurrency = (value: number) =>
    value.toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
