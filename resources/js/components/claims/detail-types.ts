export interface ClaimDetailUser {
    id: number;
    name: string;
    email: string;
}

export interface ClaimDetailLine {
    id: number;
    cpt_code: string | null;
    modifier: string | null;
    units: number | null;
    true_charge: number;
    true_balance: number;
    work_status: string;
    denial_reason: string | null;
    payer_name: string | null;
    primary_provider: string | null;
    modmed_claim_status: string | null;
    cf_invoice_date: string | null;
    invoiced_status: string | null;
    invoiced_status_date: string | null;
    credit_status: boolean | null;
    credit_status_date: string | null;
    credit_reason: string | null;
    patient_id: string | null;
    notes: string | null;
    assigned_to: ClaimDetailUser | null;
}

export interface ClaimDetail {
    id: number;
    bill_id: string;
    patient_name: string;
    patient_id: string | null;
    patient_dob: string | null;
    facility: string | null;
    service_date_start: string | null;
    service_date_end: string | null;
    service_type: string | null;
    diagnosis_codes: string[];
    line_count: number;
    total_true_charge: number;
    total_payments: number;
    total_true_balance: number;
    lines: ClaimDetailLine[];
}

export interface ClaimDetailActivity {
    id: number;
    claim_line_id: number | null;
    cpt_code: string | null;
    action: string;
    description: string;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    created_at: string;
    user: ClaimDetailUser | null;
}
