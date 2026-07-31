import { type PaginationLink } from '@/components/pagination';

export interface UserOption {
    id: number;
    name: string;
    email: string;
}

export interface StatusOption {
    id?: number;
    value: string;
    label: string;
    color?: string | null;
}

export interface ClaimLine {
    id: number;
    bill_id: string | null;
    procedure_code: string | null;
    cpt_code: string | null;
    service_date_start: string | null;
    service_date_end: string | null;
    true_charge: number | string | null;
    true_balance: number | string | null;
    primary_provider: string | null;
    payer_name: string | null;
    patient_id: string | null;
    modmed_claim_status_id: number | null;
    modmed_claim_status: string | null;
    modmed_claim_status_label: string | null;
    modmed_claim_status_color: string | null;
    cf_invoice_date: string | null;
    invoiced_status: string | null;
    invoiced_status_date: string | null;
    credit_status_id: number | null;
    credit_status: boolean | null;
    credit_status_label: string;
    credit_status_date: string | null;
    credit_reason_id: number | null;
    credit_reason: string | null;
    credit_reason_label: string | null;
    work_status_id: number | null;
    work_status: string;
    work_status_label: string;
    work_status_color: string | null;
    denial_reason_id: number | null;
    denial_reason: string | null;
    denial_reason_label: string | null;
    notes: string | null;
    source_notes: string | null;
    activity_type: string | null;
    batch_name: string | null;
    location: string | null;
    place_of_service_code: string | null;
    assigned_to: number | null;
    assignee: UserOption | null;
    is_modified: boolean;
    updated_at: string;
}

export interface ClaimGroup {
    id: number;
    bill_id: string;
    patient_name: string;
    first_name: string | null;
    last_name: string | null;
    patient_dob: string | null;
    patient_id: string | null;
    payer_name: string | null;
    primary_provider: string | null;
    facility: string | null;
    modified_by: UserOption | null;
    service_date_start: string | null;
    service_date_end: string | null;
    payments: number | string | null;
    true_charge: number | string | null;
    true_balance: number | string | null;
    modmed_claim_status_id: number | null;
    modmed_claim_status: string | null;
    modmed_claim_status_label: string | null;
    modmed_claim_status_color: string | null;
    cf_invoice_date: string | null;
    invoiced_status: string | null;
    invoiced_status_date: string | null;
    credit_status_id: number | null;
    credit_status: boolean | null;
    credit_status_label: string;
    credit_status_date: string | null;
    credit_reason_id: number | null;
    credit_reason: string | null;
    credit_reason_label: string | null;
    work_status_id: number | null;
    work_status: string;
    work_status_label: string;
    work_status_color: string | null;
    denial_reason_id: number | null;
    denial_reason: string | null;
    denial_reason_label: string | null;
    notes: string | null;
    activity_type: string | null;
    batch_name: string | null;
    location: string | null;
    place_of_service_code: string | null;
    assigned_to: number | null;
    assignee: UserOption | null;
    updated_at: string;
    line_count: number;
    cpt_codes: string[];
    is_modified: boolean;
    lines: ClaimLine[];
}

export interface ClaimPage {
    data: ClaimGroup[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export interface Summary {
    totalCount: number;
    totalTrueBalance: number;
    totalTrueCharge: number;
    totalPayments: number;
}

export interface Filters {
    [key: string]: string;
    search: string;
    modmed_claim_status: string;
    invoiced_status: string;
    payer_name: string;
    primary_provider: string;
    denial_reason: string;
    work_status: string;
    assigned_to: string;
    worked_from: string;
    worked_to: string;
    service_month: string;
    cf_invoice_from: string;
    cf_invoice_to: string;
    procedure_code: string;
    expanded: string;
    sort_by: string;
    sort_direction: string;
}

export type SortColumn =
    | 'bill_id'
    | 'patient_name'
    | 'payer_name'
    | 'primary_provider'
    | 'location'
    | 'service_date_start'
    | 'line_count'
    | 'true_charge'
    | 'true_balance'
    | 'payments'
    | 'updated_at';
