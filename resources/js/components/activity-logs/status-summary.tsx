import type { StatusSummaryItem } from '@/components/activity-logs/types';
import { formatCurrency, formatNumber } from '@/components/activity-logs/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Download } from 'lucide-react';

const statusBackground = (status: string) => {
    const colors: Record<string, string> = {
        appeal: 'bg-purple-50',
        rebilled: 'bg-blue-50',
        historical_posted_payments: 'bg-teal-50',
        corrected: 'bg-blue-50',
        paid: 'bg-emerald-50',
        patient_balance: 'bg-pink-50',
        pending: 'bg-yellow-50',
        void: 'bg-gray-100',
    };

    return colors[status] ?? 'bg-gray-50';
};

interface ActivityStatusSummaryProps {
    statuses: StatusSummaryItem[];
    description: string;
    onSelect?: (status: StatusSummaryItem) => void;
    exportHref?: string;
}

export function ActivityStatusSummary({ statuses, description, onSelect, exportHref }: ActivityStatusSummaryProps) {
    const sortedStatuses = [...statuses].sort((left, right) => right.amount - left.amount);
    const totalCount = statuses.reduce((sum, status) => sum + status.count, 0);
    const totalAmount = statuses.reduce((sum, status) => sum + status.amount, 0);

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="text-base">Status Summary</CardTitle>
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="text-primary text-base font-semibold">
                            {formatNumber(totalCount)} / {formatCurrency(totalAmount)}
                        </span>
                        {exportHref && (
                            <Button asChild size="sm" variant="outline">
                                <a href={exportHref}>
                                    <Download />
                                    Export
                                </a>
                            </Button>
                        )}
                    </div>
                </div>
                <p className="text-muted-foreground text-sm">{description}</p>
            </CardHeader>
            <CardContent>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {sortedStatuses.map((status, index) => (
                        <button
                            className={`border-muted/60 hover:border-primary/40 flex h-full w-full flex-col gap-2 rounded-lg border p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 ${statusBackground(status.status)}`}
                            disabled={status.count === 0}
                            key={status.status}
                            onClick={() => onSelect?.(status)}
                            type="button"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <p className="text-muted-foreground text-xs tracking-wide uppercase">{index === 0 ? 'Top balance' : 'Status'}</p>
                                    <h3 className="text-base font-semibold">{status.label}</h3>
                                </div>
                                <span className="bg-primary/10 text-primary rounded-full px-2.5 py-1 text-sm font-semibold">
                                    {formatNumber(status.count)}
                                </span>
                            </div>
                            <div className="mt-auto">
                                <p className="text-muted-foreground text-xs">Balance</p>
                                <p className="text-lg font-semibold">{formatCurrency(status.amount)}</p>
                            </div>
                        </button>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
