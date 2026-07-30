import type { AssigneeWorkload } from '@/components/assignments/types';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface AssignedWorkloadTableProps {
    workloads: AssigneeWorkload[];
    isLoading: boolean;
    formatCurrency: (value: number | null) => string;
}

export function AssignedWorkloadTable({ workloads, isLoading, formatCurrency }: AssignedWorkloadTableProps) {
    const totalClaimGroups = workloads.reduce((total, workload) => total + workload.claim_groups, 0);
    const totalClaimLines = workloads.reduce((total, workload) => total + workload.claim_lines, 0);
    const totalBalance = workloads.reduce((total, workload) => total + (workload.total_true_balance ?? 0), 0);
    const hasBalance = workloads.some((workload) => workload.balance_rows > 0);

    return (
        <Card className="overflow-hidden">
            <CardHeader className="border-b">
                <CardTitle>Assigned workload</CardTitle>
                <CardDescription>Current Bill ID and CPT-line counts for each available assignee.</CardDescription>
            </CardHeader>
            <DataLoadingOverlay isLoading={isLoading} label="Refreshing assigned counts...">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[720px] text-sm">
                        <thead className="bg-muted/60 text-muted-foreground text-left text-xs uppercase">
                            <tr>
                                <th className="p-4">Assignee</th>
                                <th className="p-4 text-right">Bill IDs</th>
                                <th className="p-4 text-right">CPT lines</th>
                                <th className="p-4 text-right">True Balance</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {workloads.map((workload) => (
                                <tr className="hover:bg-muted/30" key={workload.id}>
                                    <td className="p-4">
                                        <p className="font-medium">{workload.name}</p>
                                        <p className="text-muted-foreground text-xs">{workload.email}</p>
                                    </td>
                                    <td className="p-4 text-right tabular-nums">{workload.claim_groups.toLocaleString()}</td>
                                    <td className="p-4 text-right tabular-nums">{workload.claim_lines.toLocaleString()}</td>
                                    <td className="p-4 text-right font-medium tabular-nums">{formatCurrency(workload.total_true_balance)}</td>
                                </tr>
                            ))}
                            {workloads.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground p-12 text-center" colSpan={4}>
                                        No assignees are available for this account.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        {workloads.length > 0 && (
                            <tfoot className="bg-muted/40 border-t font-semibold">
                                <tr>
                                    <td className="p-4">Total assigned</td>
                                    <td className="p-4 text-right tabular-nums">{totalClaimGroups.toLocaleString()}</td>
                                    <td className="p-4 text-right tabular-nums">{totalClaimLines.toLocaleString()}</td>
                                    <td className="p-4 text-right tabular-nums">{formatCurrency(hasBalance ? totalBalance : null)}</td>
                                </tr>
                            </tfoot>
                        )}
                    </table>
                </div>
            </DataLoadingOverlay>
        </Card>
    );
}
