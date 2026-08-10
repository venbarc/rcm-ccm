import { ModMedClaimStatusBadge } from '@/components/claims/modmed-claim-status-badge';
import type { DashboardFinancialSummary, DashboardFinancialSummaryRow } from '@/components/dashboard/types';
import { formatCount, formatCurrency, formatNumber, formatPercent } from '@/components/dashboard/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';

interface FinancialSummaryCardProps {
    description: string;
    filters: ReactNode;
    groupHeading: string;
    groupKind: 'cpt' | 'modmed-status';
    summary: DashboardFinancialSummary;
    title: string;
}

function GroupValue({ kind, row }: { kind: FinancialSummaryCardProps['groupKind']; row: DashboardFinancialSummaryRow }) {
    if (kind === 'modmed-status') {
        return row.group ? (
            <ModMedClaimStatusBadge color={row.groupColor} label={row.groupLabel} status={row.group} />
        ) : (
            <Badge className="border-slate-300 bg-slate-50 text-slate-600" variant="outline">
                No status
            </Badge>
        );
    }

    return row.group ? (
        <Badge className="font-mono text-xs" variant="outline">
            {row.group}
        </Badge>
    ) : (
        <span className="text-muted-foreground">No CPT</span>
    );
}

function FinancialCells({ row }: { row: DashboardFinancialSummaryRow }) {
    const workspace = useClaimWorkspace();

    return (
        <>
            <td className="px-3 py-3 text-right tabular-nums">{formatCount(row.billCount)}</td>
            <td className="px-3 py-3 text-right tabular-nums">{formatCount(row.cptCount)}</td>
            <td className="px-3 py-3 text-right tabular-nums">{formatNumber(row.units)}</td>
            <td className="px-3 py-3 text-right tabular-nums">{formatCurrency(row.trueCharge)}</td>
            <td className="px-3 py-3 text-right font-medium text-green-700 tabular-nums">{formatCurrency(row.payments)}</td>
            {workspace.showTrueBalance && (
                <td className="px-3 py-3 text-right font-medium text-orange-700 tabular-nums">{formatCurrency(row.trueBalance)}</td>
            )}
            {workspace.showTrueBalance && <td className="px-3 py-3 text-right font-medium tabular-nums">{formatPercent(row.collectionPercent)}</td>}
            <td className="px-3 py-3 text-right tabular-nums">{formatCurrency(row.cfInvoiceAmount)}</td>
        </>
    );
}

export function FinancialSummaryCard({ description, filters, groupHeading, groupKind, summary, title }: FinancialSummaryCardProps) {
    const workspace = useClaimWorkspace();

    return (
        <Card className="w-full max-w-full min-w-0 overflow-hidden">
            <CardHeader className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle className="text-xl">{title}</CardTitle>
                        <CardDescription className="mt-1">{description}</CardDescription>
                    </div>
                    <Badge className="border-border bg-secondary text-secondary-foreground w-fit gap-1.5" variant="outline">
                        <ShieldCheck className="size-3.5" />
                        Admin only
                    </Badge>
                </div>
                <div className="border-muted/60 bg-muted/15 rounded-lg border p-3">{filters}</div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="w-full max-w-full overflow-x-auto overscroll-x-contain">
                    <table className="w-full min-w-[1180px] text-sm">
                        <thead className="bg-muted/60 text-muted-foreground border-y">
                            <tr>
                                <th className="w-56 px-3 py-3 text-left font-semibold">{groupHeading}</th>
                                <th className="px-3 py-3 text-right font-semibold">Distinct {workspace.identifierLabel}s</th>
                                <th className="px-3 py-3 text-right font-semibold">{workspace.procedureLinesLabel}</th>
                                <th className="px-3 py-3 text-right font-semibold">Units</th>
                                <th className="px-3 py-3 text-right font-semibold">True Charge</th>
                                <th className="px-3 py-3 text-right font-semibold">{workspace.paymentsLabel}</th>
                                {workspace.showTrueBalance && <th className="px-3 py-3 text-right font-semibold">True Balance</th>}
                                {workspace.showTrueBalance && <th className="px-3 py-3 text-right font-semibold">Collection %</th>}
                                <th className="px-3 py-3 text-right font-semibold">CF Invoice Amount</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {summary.rows.map((row, index) => (
                                <tr className="even:bg-muted/20 hover:bg-muted/40 transition-colors" key={row.group ?? `empty-${index}`}>
                                    <td className="px-3 py-3 font-semibold">
                                        <GroupValue kind={groupKind} row={row} />
                                    </td>
                                    <FinancialCells row={row} />
                                </tr>
                            ))}
                            {summary.rows.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-4 py-10 text-center" colSpan={workspace.showTrueBalance ? 9 : 7}>
                                        No summary data found for the selected date range.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot className="bg-primary/10 border-t-2 font-semibold">
                            <tr>
                                <td className="px-3 py-3">Grand Total</td>
                                <FinancialCells row={summary.total} />
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
