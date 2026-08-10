import type { DistributionPreview as DistributionPreviewData } from '@/components/assignments/types';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { Shuffle, Target } from 'lucide-react';

interface DistributionPreviewProps {
    activeGroupBy: string;
    formatCurrency: (value: number | null) => string;
    isDistributing: boolean;
    isLoading: boolean;
    onDistribute: () => void;
    preview: DistributionPreviewData | null;
    selectedAssigneeCount: number;
    selectedValueCount: number;
}

export function DistributionPreview({
    activeGroupBy,
    formatCurrency,
    isDistributing,
    isLoading,
    onDistribute,
    preview,
    selectedAssigneeCount,
    selectedValueCount,
}: DistributionPreviewProps) {
    const workspace = useClaimWorkspace();

    return (
        <Card>
            <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <CardTitle className="flex items-center gap-2">
                        <Target className="size-5" /> Distribution preview
                    </CardTitle>
                    <CardDescription>
                        {workspace.showTrueBalance
                            ? `Largest ${workspace.identifierLabel} balances are placed first into the assignee with the lowest running True Balance.`
                            : `Complete ${workspace.identifierLabel} groups are distributed evenly without splitting their ${workspace.procedureLabel} lines.`}
                    </CardDescription>
                </div>
                <Button disabled={!preview || preview.total_claims === 0 || isDistributing || isLoading} onClick={onDistribute}>
                    <Shuffle /> {isDistributing ? 'Distributing...' : 'Distribute claim groups'}
                </Button>
            </CardHeader>
            <CardContent>
                <DataLoadingOverlay className="min-h-24" isLoading={isLoading} label="Building grouped preview...">
                    {selectedAssigneeCount === 0 ? (
                        <p className="text-muted-foreground py-10 text-center text-sm">Select at least one assignee.</p>
                    ) : activeGroupBy !== 'all' && selectedValueCount === 0 ? (
                        <p className="text-muted-foreground py-10 text-center text-sm">
                            Apply a dropdown selection to preview that distribution slice.
                        </p>
                    ) : preview ? (
                        <div className="space-y-5">
                            <div className="grid gap-4 sm:grid-cols-4">
                                <div>
                                    <p className="text-muted-foreground text-xs uppercase">Claim groups</p>
                                    <p className="text-xl font-semibold">{preview.total_claims.toLocaleString()}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs uppercase">{workspace.procedureLabel} lines</p>
                                    <p className="text-xl font-semibold">{preview.total_lines.toLocaleString()}</p>
                                </div>
                                {workspace.showTrueBalance && (
                                    <div>
                                        <p className="text-muted-foreground text-xs uppercase">Total True Balance</p>
                                        <p className="text-primary text-xl font-semibold">{formatCurrency(preview.total_balance)}</p>
                                    </div>
                                )}
                                {workspace.showTrueBalance && (
                                    <div>
                                        <p className="text-muted-foreground text-xs uppercase">Target per assignee</p>
                                        <p className="text-xl font-semibold">{formatCurrency(preview.target_balance)}</p>
                                    </div>
                                )}
                            </div>
                            <div className="overflow-x-auto rounded-lg border">
                                <table className="w-full min-w-[680px] text-sm">
                                    <thead className="bg-muted/60 text-muted-foreground text-left text-xs uppercase">
                                        <tr>
                                            <th className="p-3">Assignee</th>
                                            <th className="p-3 text-right">Claim groups</th>
                                            <th className="p-3 text-right">{workspace.procedureLabel} lines</th>
                                            {workspace.showTrueBalance && <th className="p-3 text-right">True Balance</th>}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {preview.distribution.map((bucket) => (
                                            <tr key={bucket.id}>
                                                <td className="p-3">
                                                    <p className="font-medium">{bucket.name}</p>
                                                    <p className="text-muted-foreground text-xs">{bucket.email}</p>
                                                </td>
                                                <td className="p-3 text-right">{bucket.assign_count.toLocaleString()}</td>
                                                <td className="p-3 text-right">{bucket.assign_line_count.toLocaleString()}</td>
                                                {workspace.showTrueBalance && (
                                                    <td className="p-3 text-right font-medium">{formatCurrency(bucket.assign_balance)}</td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {workspace.showTrueBalance && preview.balance_rows === 0 && (
                                <p className="border-border bg-secondary text-secondary-foreground rounded-lg border p-3 text-sm">
                                    True Balance is not present yet. Bill IDs will be distributed evenly by group count now; the same allocator will
                                    automatically balance by True Balance once imported.
                                </p>
                            )}
                        </div>
                    ) : null}
                </DataLoadingOverlay>
            </CardContent>
        </Card>
    );
}
