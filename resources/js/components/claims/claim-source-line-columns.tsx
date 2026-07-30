import { ModMedClaimStatusBadge } from '@/components/claims/modmed-claim-status-badge';
import { EMPTY_VALUE, currency, date } from '@/components/claims/utils';

export interface ClaimSourceLine {
    cpt_code: string | null;
    primary_provider: string | null;
    payer_name: string | null;
    modmed_claim_status: string | null;
    cf_invoice_date: string | null;
    patient_id: string | null;
    true_charge: number | string | null;
    true_balance: number | string | null;
}

export function ClaimSourceLineHeaders() {
    return (
        <>
            <th className="px-3 py-3 text-left font-medium">CPT Code</th>
            <th className="px-3 py-3 text-left font-medium">Primary Provider</th>
            <th className="px-3 py-3 text-left font-medium">Payer</th>
            <th className="px-3 py-3 text-left font-medium">ModMed Claim Status</th>
            <th className="px-3 py-3 text-left font-medium">CF Invoice Date</th>
            <th className="px-3 py-3 text-left font-medium">Patient MRN</th>
            <th className="px-3 py-3 text-right font-medium">True Charge</th>
            <th className="px-3 py-3 text-right font-medium">True Balance</th>
        </>
    );
}

export function ClaimSourceLineCells({ line }: { line: ClaimSourceLine }) {
    return (
        <>
            <td className="px-3 py-3 font-semibold">{line.cpt_code || EMPTY_VALUE}</td>
            <td className="px-3 py-3">{line.primary_provider || EMPTY_VALUE}</td>
            <td className="px-3 py-3">{line.payer_name || EMPTY_VALUE}</td>
            <td className="px-3 py-3">
                <ModMedClaimStatusBadge status={line.modmed_claim_status} />
            </td>
            <td className="px-3 py-3 whitespace-nowrap">{date(line.cf_invoice_date)}</td>
            <td className="px-3 py-3">{line.patient_id || EMPTY_VALUE}</td>
            <td className="px-3 py-3 text-right tabular-nums">{currency(line.true_charge)}</td>
            <td className="px-3 py-3 text-right font-medium text-orange-600 tabular-nums">{currency(line.true_balance)}</td>
        </>
    );
}
