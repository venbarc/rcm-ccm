import type { StatusSummaryItem } from '@/components/activity-logs/types';
import { formatCurrency, formatNumber } from '@/components/activity-logs/types';
import { workStatusBackgroundStyle } from '@/components/claims/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { Download } from 'lucide-react';

interface ActivityStatusSummaryProps {
    statuses: StatusSummaryItem[];
    description: string;
    onSelect?: (status: StatusSummaryItem) => void;
    exportHref?: string;
}

export function ActivityStatusSummary({ statuses, description, onSelect, exportHref }: ActivityStatusSummaryProps) {
    const workspace = useClaimWorkspace();
    const sortedStatuses = [...statuses].sort((left, right) => (workspace.showTrueBalance ? right.amount - left.amount : right.count - left.count));
    const totalCount = statuses.reduce((sum, status) => sum + status.count, 0);
    const totalAmount = statuses.reduce((sum, status) => sum + status.amount, 0);

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="text-base">Status Summary</CardTitle>
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="text-primary text-base font-semibold">
                            {formatNumber(totalCount)}
                            {workspace.showTrueBalance ? ` / ${formatCurrency(totalAmount)}` : ''}
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
                            className="border-muted/60 hover:border-primary/40 flex h-full w-full flex-col gap-2 rounded-lg border p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                            disabled={status.count === 0}
                            key={status.status}
                            onClick={() => onSelect?.(status)}
                            style={workStatusBackgroundStyle(status.color)}
                            type="button"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                        {index === 0 ? (workspace.showTrueBalance ? 'Top balance' : 'Top count') : 'Status'}
                                    </p>
                                    <h3 className="text-base font-semibold">{status.label}</h3>
                                </div>
                                <span className="bg-primary/10 text-primary rounded-full px-2.5 py-1 text-sm font-semibold">
                                    {formatNumber(status.count)}
                                </span>
                            </div>
                            {workspace.showTrueBalance && (
                                <div className="mt-auto">
                                    <p className="text-muted-foreground text-xs">Balance</p>
                                    <p className="text-lg font-semibold">{formatCurrency(status.amount)}</p>
                                </div>
                            )}
                        </button>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
