import { type PaginationLink } from '@/components/pagination';

export interface UserOption {
    id: number;
    name: string;
    email: string;
}

export interface StatusOption {
    value: string;
    label: string;
}

export interface ClaimLine {
    id: number;
    bill_id: string | null;
    procedure_code: string | null;
    cpt_code: string | null;
    service_date_start: string | null;
    service_date_end: string | null;
    payments: number | string | null;
    true_charge: number | string | null;
    adjustments: number | string | null;
    true_balance: number | string | null;
    rendering_provider: string | null;
    payer_name: string | null;
    patient_id: string | null;
    priority: string | null;
    claim_status: string | null;
    work_status: string;
    denial_reason: string | null;
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
    external_id: string;
    patient_name: string;
    first_name: string | null;
    last_name: string | null;
    patient_dob: string | null;
    patient_id: string | null;
    payer_name: string | null;
    rendering_provider: string | null;
    facility: string | null;
    modified_by: UserOption | null;
    service_date_start: string | null;
    service_date_end: string | null;
    payments: number | string | null;
    true_charge: number | string | null;
    adjustments: number | string | null;
    true_balance: number | string | null;
    claim_status: string | null;
    work_status: string;
    denial_reason: string | null;
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
    bill_ids: string[];
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
    claim_status: string;
    payer_name: string;
    rendering_provider: string;
    denial_reason: string;
    work_status: string;
    assigned_to: string;
    worked_from: string;
    worked_to: string;
    service_month: string;
    procedure_code: string;
    expanded: string;
    sort_by: string;
    sort_direction: string;
}

export type SortColumn =
    | 'service_date_start'
    | 'service_date_end'
    | 'first_name'
    | 'last_name'
    | 'true_charge'
    | 'true_balance'
    | 'payments'
    | 'updated_at';
