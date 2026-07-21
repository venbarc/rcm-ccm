export interface AssignmentSummary {
    claim_groups: number;
    claim_lines: number;
    balance_rows: number;
    total_true_balance: number | null;
    assigned_claim_groups: number;
    assigned_claim_lines: number;
    assigned_balance_rows: number;
    assigned_total_true_balance: number | null;
}

export interface AssigneeWorkload {
    id: number;
    name: string;
    email: string;
    claim_groups: number;
    claim_lines: number;
    balance_rows: number;
    total_true_balance: number | null;
}

export interface DistributionBucket {
    id: number;
    name: string;
    email: string;
    assign_count: number;
    assign_line_count: number;
    assign_balance: number | null;
}

export interface DistributionPreview {
    total_claims: number;
    total_lines: number;
    balance_rows: number;
    total_balance: number | null;
    assignee_count: number;
    target_balance: number | null;
    distribution: DistributionBucket[];
}
