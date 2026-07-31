import type { DashboardCreditStatusSummary } from '@/components/dashboard/types';
import { formatCount } from '@/components/dashboard/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
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
                            CPT-line counts by credit status. A selected date range includes only dated Yes records.
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
                <table className="w-full table-fixed text-sm">
                    <colgroup>
                        <col />
                        <col className="w-40" />
                    </colgroup>
                    <thead className="bg-muted/60 text-muted-foreground border-y">
                        <tr>
                            <th className="px-4 py-3 text-left font-semibold">Credit Status</th>
                            <th className="px-4 py-3 text-right font-semibold">Total Count</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {summary.rows.map((row) => (
                            <tr className="even:bg-muted/20 hover:bg-muted/40 transition-colors" key={row.status}>
                                <td className="px-4 py-3">
                                    <Badge
                                        className={cn(
                                            'font-medium',
                                            row.status === 'yes'
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                                                : 'border-slate-300 bg-slate-50 text-slate-700',
                                        )}
                                        variant="outline"
                                    >
                                        {row.label}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3 text-right font-semibold tabular-nums">{formatCount(row.count)}</td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot className="bg-primary/10 border-t-2 font-semibold">
                        <tr>
                            <td className="px-4 py-3">Grand Total</td>
                            <td className="px-4 py-3 text-right tabular-nums">{formatCount(summary.totalCount)}</td>
                        </tr>
                    </tfoot>
                </table>
            </CardContent>
        </Card>
    );
}
