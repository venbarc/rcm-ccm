import type { DashboardInvoicedSummary } from '@/components/dashboard/types';
import { formatNumber } from '@/components/dashboard/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';

interface InvoicedSummaryCardProps {
    filters: ReactNode;
    summary: DashboardInvoicedSummary;
}

export function InvoicedSummaryCard({ filters, summary }: InvoicedSummaryCardProps) {
    return (
        <Card className="h-full w-full max-w-full min-w-0 overflow-hidden">
            <CardHeader className="space-y-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle className="text-xl">Invoiced Summary</CardTitle>
                        <CardDescription className="mt-1">Invoiced units grouped by CPT for the selected CF invoice date range.</CardDescription>
                    </div>
                    <Badge className="w-fit gap-1.5 border-blue-200 bg-blue-50 text-blue-800" variant="outline">
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
                                <th className="px-4 py-3 text-left font-semibold">CPT</th>
                                <th className="px-4 py-3 text-left font-semibold">Units</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {summary.rows.map((row, index) => (
                                <tr className="even:bg-muted/20 hover:bg-muted/40 transition-colors" key={row.cpt ?? `empty-${index}`}>
                                    <td className="px-4 py-3 font-mono font-semibold">{row.cpt ?? 'No CPT'}</td>
                                    <td className="px-4 py-3 text-left tabular-nums">{formatNumber(row.units)}</td>
                                </tr>
                            ))}
                            {summary.rows.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-4 py-10 text-center" colSpan={2}>
                                        No invoiced CPT data found for the selected date range.
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
