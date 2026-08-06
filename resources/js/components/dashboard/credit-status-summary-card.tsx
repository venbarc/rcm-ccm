import type { DashboardCreditStatusSummary } from '@/components/dashboard/types';
import { formatCount, formatCurrency, formatNumber } from '@/components/dashboard/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';

interface CreditStatusSummaryCardProps {
    filters: ReactNode;
    summary: DashboardCreditStatusSummary;
}

export function CreditStatusSummaryCard({ filters, summary }: CreditStatusSummaryCardProps) {
    return (
        <Card className="h-full w-full max-w-full min-w-0 overflow-hidden">
            <CardHeader className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle className="text-xl">Credit Status Summary</CardTitle>
                        <CardDescription className="mt-1">
                            Credit Status Yes lines grouped by CPT code for the selected Credit Status Date range.
                        </CardDescription>
                    </div>
                    <Badge className="w-fit gap-1.5 border-blue-200 bg-blue-50 text-blue-800" variant="outline">
                        <ShieldCheck className="size-3.5" />
                        Admin only
                    </Badge>
                </div>
                <div className="border-muted/60 bg-muted/15 rounded-lg border p-3">{filters}</div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="w-full max-w-full overflow-x-auto overscroll-x-contain">
                    <table className="w-full min-w-[780px] text-sm">
                        <thead className="bg-muted/60 text-muted-foreground border-y">
                            <tr>
                                <th className="w-32 px-3 py-3 text-left font-semibold">CPT Code</th>
                                <th className="px-3 py-3 text-right font-semibold">Count of CPT</th>
                                <th className="px-3 py-3 text-right font-semibold">Sum of Units</th>
                                <th className="px-3 py-3 text-right font-semibold">Sum of True Charge</th>
                                <th className="px-3 py-3 text-right font-semibold">Sum of CF Invoice Amount</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {summary.rows.map((row, index) => (
                                <tr className="even:bg-muted/20 hover:bg-muted/40 transition-colors" key={row.cpt ?? `empty-${index}`}>
                                    <td className="px-3 py-3 font-mono font-semibold">{row.cpt ?? 'No CPT'}</td>
                                    <td className="px-3 py-3 text-right font-semibold tabular-nums">{formatCount(row.count)}</td>
                                    <td className="px-3 py-3 text-right tabular-nums">{formatNumber(row.units)}</td>
                                    <td className="px-3 py-3 text-right tabular-nums">{formatCurrency(row.trueCharge)}</td>
                                    <td className="px-3 py-3 text-right tabular-nums">{formatCurrency(row.cfInvoiceAmount)}</td>
                                </tr>
                            ))}
                            {summary.rows.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-4 py-10 text-center" colSpan={5}>
                                        No Credit Status Yes lines found for the selected date range.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        <tfoot className="bg-primary/10 border-t-2 font-semibold">
                            <tr>
                                <td className="px-3 py-3">Grand Total</td>
                                <td className="px-3 py-3 text-right tabular-nums">{formatCount(summary.totalCount)}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{formatNumber(summary.totalUnits)}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{formatCurrency(summary.totalTrueCharge)}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{formatCurrency(summary.totalCfInvoiceAmount)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
