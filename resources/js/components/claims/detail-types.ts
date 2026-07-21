export interface ClaimDetailUser {
    id: number;
    name: string;
    email: string;
}

export interface ClaimDetailLine {
    id: number;
    cpt_code: string | null;
    modifier: string | null;
    priority: string | null;
    units: number | null;
    charges: number;
    paid: number;
    adjustments: number;
    balance: number;
    work_status: string;
    denial_reason: string | null;
    payer_name: string | null;
    rendering_provider: string | null;
    patient_id: string | null;
    notes: string | null;
    assigned_to: ClaimDetailUser | null;
}

export interface ClaimDetail {
    id: number;
    external_id: string;
    patient_name: string;
    patient_id: string | null;
    patient_dob: string | null;
    facility: string | null;
    service_date_start: string | null;
    service_date_end: string | null;
    service_type: string | null;
    diagnosis_codes: string[];
    line_count: number;
    total_charges: number;
    total_paid: number;
    total_adjustments: number;
    total_balance: number;
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
