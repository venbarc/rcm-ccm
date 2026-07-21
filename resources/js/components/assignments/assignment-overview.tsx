import type { AssignmentSummary } from '@/components/assignments/types';
import { Card, CardContent } from '@/components/ui/card';

interface AssignmentOverviewProps {
    summary: AssignmentSummary;
    formatCurrency: (value: number | null) => string;
}

export function AssignmentOverview({ summary, formatCurrency }: AssignmentOverviewProps) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="bg-muted/10 rounded-lg border p-4">
                        <p className="text-muted-foreground text-xs font-semibold uppercase">Remaining Claim IDs</p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.claim_groups.toLocaleString()}{' '}
                            <span className="text-muted-foreground text-sm font-normal">({summary.claim_lines.toLocaleString()} CPT lines)</span>
                        </p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            True Balance <span className="text-destructive font-semibold">{formatCurrency(summary.total_true_balance)}</span>
                        </p>
                    </div>
                    <div className="bg-muted/10 rounded-lg border p-4">
                        <p className="text-muted-foreground text-xs font-semibold uppercase">Assigned Claim IDs</p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.assigned_claim_groups.toLocaleString()}{' '}
                            <span className="text-muted-foreground text-sm font-normal">
                                ({summary.assigned_claim_lines.toLocaleString()} CPT lines)
                            </span>
                        </p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            True Balance <span className="text-primary font-semibold">{formatCurrency(summary.assigned_total_true_balance)}</span>
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
