import type { DashboardInvoicedSummary } from '@/components/dashboard/types';
import { formatNumber } from '@/components/dashboard/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';

interface InvoicedSummaryCardProps {
    filters: ReactNode;
    summary: DashboardInvoicedSummary;
}

export function InvoicedSummaryCard({ filters, summary }: InvoicedSummaryCardProps) {
    const workspace = useClaimWorkspace();
    const dateLabel = workspace.showInvoiceFields ? 'CF invoice date' : workspace.serviceDateLabel.toLowerCase();

    return (
        <Card className="h-full w-full max-w-full min-w-0 overflow-hidden">
            <CardHeader className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle className="text-xl">Invoiced Summary</CardTitle>
                        <CardDescription className="mt-1">
                            Invoiced units grouped by {workspace.procedureLabel} for the selected {dateLabel} range.
                        </CardDescription>
                    </div>
                    <Badge className="border-border bg-secondary text-secondary-foreground w-fit gap-1.5" variant="outline">
                        <ShieldCheck className="size-3.5" />
                        Admin only
                    </Badge>
                </div>
                <div className="border-muted/60 bg-muted/15 rounded-lg border p-3">{filters}</div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="w-full overflow-x-auto">
                    <table className="w-full min-w-[420px] table-fixed text-sm">
                        <colgroup>
                            <col className="w-40" />
                            <col />
                        </colgroup>
                        <thead className="bg-muted/60 text-muted-foreground border-y">
                            <tr>
                                <th className="px-4 py-3 text-left font-semibold">{workspace.procedureLabel}</th>
                                <th className="px-4 py-3 text-left font-semibold">Units</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {summary.rows.map((row, index) => (
                                <tr className="even:bg-muted/20 hover:bg-muted/40 transition-colors" key={row.cpt ?? `empty-${index}`}>
                                    <td className="px-4 py-3 font-mono font-semibold">{row.cpt ?? `No ${workspace.procedureLabel}`}</td>
                                    <td className="px-4 py-3 text-left tabular-nums">{formatNumber(row.units)}</td>
                                </tr>
                            ))}
                            {summary.rows.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-4 py-10 text-center" colSpan={2}>
                                        No invoiced {workspace.procedureLabel.toLowerCase()} data found for the selected date range.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot className="bg-primary/10 border-t-2 font-semibold">
                            <tr>
                                <td className="px-4 py-3">Grand Total</td>
                                <td className="px-4 py-3 text-left tabular-nums">{formatNumber(summary.totalUnits)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
