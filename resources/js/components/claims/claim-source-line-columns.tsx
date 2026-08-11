import { CreditReasonBadge, CreditStatusBadge, InvoicedStatusBadge } from '@/components/claims/invoiced-status-badge';
import { ModMedClaimStatusBadge } from '@/components/claims/modmed-claim-status-badge';
import { currency, date, EMPTY_VALUE } from '@/components/claims/utils';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';

export interface ClaimSourceLine {
    cpt_code: string | null;
    primary_provider: string | null;
    payer_name: string | null;
    modmed_claim_status: string | null;
    modmed_claim_status_label: string | null;
    modmed_claim_status_color: string | null;
    cf_invoice_date: string | null;
    invoiced_status: string | null;
    invoiced_status_date: string | null;
    credit_status: boolean | null;
    credit_status_label: string;
    credit_status_date: string | null;
    credit_reason: string | null;
    credit_reason_label: string | null;
    patient_id: string | null;
    true_charge: number | string | null;
    true_balance: number | string | null;
    payments?: number | string | null;
    units?: number | string | null;
    claim_cpt?: string | null;
    charge_amount?: number | string | null;
    cf_invoice_amount?: number | string | null;
    total_payment?: number | string | null;
    true_charge_per_unit?: number | string | null;
    insurance_balance?: number | string | null;
    patient_balance?: number | string | null;
    total_balance?: number | string | null;
}

interface ClaimSourceLineVisibility {
    showPayer?: boolean;
    showPrimaryProvider?: boolean;
}

export function ClaimSourceLineHeaders({ showPayer = true, showPrimaryProvider = true }: ClaimSourceLineVisibility = {}) {
    const workspace = useClaimWorkspace();

    if (workspace.isPrinciple) {
        return (
            <>
                <th className="px-3 py-3 text-left font-medium">Procedure Code</th>
                {showPrimaryProvider && <th className="px-3 py-3 text-left font-medium">Rendering Provider</th>}
                {showPayer && <th className="px-3 py-3 text-left font-medium">Responsible Payer</th>}
                <th className="px-3 py-3 text-right font-medium">Units</th>
                <th className="px-3 py-3 text-right font-medium">Charge Amount</th>
                <th className="px-3 py-3 text-right font-medium">Total Payment</th>
                <th className="px-3 py-3 text-right font-medium">True Charge</th>
                <th className="px-3 py-3 text-right font-medium">True Charge Per Unit</th>
                <th className="px-3 py-3 text-right font-medium">Insurance Balance</th>
                <th className="px-3 py-3 text-right font-medium">Patient Balance</th>
                <th className="px-3 py-3 text-right font-medium">Total Balance</th>
            </>
        );
    }

    return (
        <>
            <th className="px-3 py-3 text-left font-medium">CPT Code</th>
            {showPrimaryProvider && <th className="px-3 py-3 text-left font-medium">Primary Provider</th>}
            {showPayer && <th className="px-3 py-3 text-left font-medium">Payer</th>}
            <th className="px-3 py-3 text-left font-medium">ModMed Claim Status</th>
            <th className="px-3 py-3 text-left font-medium">CF Invoice Date</th>
            <th className="px-3 py-3 text-left font-medium">Invoiced Status</th>
            <th className="px-3 py-3 text-left font-medium">Invoiced Status Date</th>
            <th className="px-3 py-3 text-left font-medium">Credit Status</th>
            <th className="px-3 py-3 text-left font-medium">Credit Status Date</th>
            <th className="px-3 py-3 text-left font-medium">Credit Reason</th>
            <th className="px-3 py-3 text-left font-medium">Patient MRN</th>
            <th className="px-3 py-3 text-right font-medium">True Charge</th>
            <th className="px-3 py-3 text-right font-medium">Payments</th>
            <th className="px-3 py-3 text-right font-medium">True Balance</th>
        </>
    );
}

export function ClaimSourceLineCells({ line, showPayer = true, showPrimaryProvider = true }: { line: ClaimSourceLine } & ClaimSourceLineVisibility) {
    const workspace = useClaimWorkspace();

    if (workspace.isPrinciple) {
        return (
            <>
                <td className="px-3 py-3 font-semibold">{line.cpt_code || EMPTY_VALUE}</td>
                {showPrimaryProvider && <td className="px-3 py-3">{line.primary_provider || EMPTY_VALUE}</td>}
                {showPayer && <td className="px-3 py-3">{line.payer_name || EMPTY_VALUE}</td>}
                <td className="px-3 py-3 text-right tabular-nums">{line.units ?? EMPTY_VALUE}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.charge_amount ?? null)}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.total_payment ?? null)}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.true_charge)}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.true_charge_per_unit ?? null)}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.insurance_balance ?? null)}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.patient_balance ?? null)}</td>
                <td className="px-3 py-3 text-right tabular-nums">{currency(line.total_balance ?? null)}</td>
            </>
        );
    }

    return (
        <>
            <td className="px-3 py-3 font-semibold">{line.cpt_code || EMPTY_VALUE}</td>
            {showPrimaryProvider && <td className="px-3 py-3">{line.primary_provider || EMPTY_VALUE}</td>}
            {showPayer && <td className="px-3 py-3">{line.payer_name || EMPTY_VALUE}</td>}
            <td className="px-3 py-3">
                <ModMedClaimStatusBadge
                    color={line.modmed_claim_status_color}
                    label={line.modmed_claim_status_label}
                    status={line.modmed_claim_status}
                />
            </td>
            <td className="px-3 py-3 whitespace-nowrap">{date(line.cf_invoice_date)}</td>
            <td className="px-3 py-3">
                <InvoicedStatusBadge status={line.invoiced_status} />
            </td>
            <td className="px-3 py-3 whitespace-nowrap">{date(line.invoiced_status_date)}</td>
            <td className="px-3 py-3">
                <CreditStatusBadge credited={line.credit_status} label={line.credit_status_label} />
            </td>
            <td className="px-3 py-3 whitespace-nowrap">{date(line.credit_status_date)}</td>
            <td className="px-3 py-3">
                <CreditReasonBadge label={line.credit_reason_label} reason={line.credit_reason} />
            </td>
            <td className="px-3 py-3">{line.patient_id || EMPTY_VALUE}</td>
            <td className="px-3 py-3 text-right tabular-nums">{currency(line.true_charge)}</td>
            <td className="px-3 py-3 text-right font-medium text-green-600 tabular-nums">{currency(line.payments ?? null)}</td>
            <td className="px-3 py-3 text-right font-medium text-orange-600 tabular-nums">{currency(line.true_balance)}</td>
        </>
    );
}
