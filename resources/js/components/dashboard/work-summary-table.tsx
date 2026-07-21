import type { WorkSummary } from './types';
import { formatCurrency } from './types';

export function WorkSummaryTable({ summary }: { summary: WorkSummary }) {
    const pendingCount = Math.max(summary.totalCount - summary.workedCount, 0);
    const pendingAmount = Math.max(summary.totalAmount - summary.workedAmount, 0);
    const workedPercent = summary.totalAmount > 0 ? Math.min((summary.workedAmount / summary.totalAmount) * 100, 100) : 0;

    return (
        <div className="border-muted/70 bg-card overflow-hidden rounded-md border">
            <div className="border-border bg-primary/10 grid grid-cols-[minmax(0,1.2fr)_minmax(90px,0.8fr)_minmax(130px,1fr)] border-b text-sm font-semibold">
                <div className="px-3 py-2">Total claims</div>
                <div className="border-border border-l px-3 py-2 text-right tabular-nums">{summary.totalCount.toLocaleString()}</div>
                <div className="border-border border-l px-3 py-2 text-right tabular-nums">{formatCurrency(summary.totalAmount)}</div>
            </div>
            <div className="border-border bg-muted/20 grid grid-cols-[minmax(0,1.2fr)_minmax(90px,0.8fr)_minmax(130px,1fr)] border-b text-sm font-semibold">
                <div className="px-3 py-2">Worked claims</div>
                <div className="border-border text-primary border-l px-3 py-2 text-right tabular-nums">{summary.workedCount.toLocaleString()}</div>
                <div className="border-border text-primary border-l px-3 py-2 text-right tabular-nums">{formatCurrency(summary.workedAmount)}</div>
            </div>
            <div className="bg-muted/10 grid grid-cols-[minmax(0,1.2fr)_minmax(90px,0.8fr)_minmax(130px,1fr)] text-sm font-semibold">
                <div className="px-3 py-2">Pending claims</div>
                <div className="border-border border-l px-3 py-2 text-right text-red-600 tabular-nums">{pendingCount.toLocaleString()}</div>
                <div className="border-border border-l px-3 py-2 text-right text-red-600 tabular-nums">{formatCurrency(pendingAmount)}</div>
            </div>
            <div className="border-border bg-card space-y-2 border-t px-3 py-3">
                <div className="flex items-center justify-between text-xs">
                    <span className="text-muted-foreground font-medium">Progress</span>
                    <span className="font-semibold">{workedPercent.toFixed(2)}%</span>
                </div>
                <div className="bg-primary/15 h-3 overflow-hidden rounded-full">
                    <div className="bg-primary h-full rounded-full transition-all" style={{ width: `${workedPercent}%` }} />
                </div>
            </div>
        </div>
    );
}
