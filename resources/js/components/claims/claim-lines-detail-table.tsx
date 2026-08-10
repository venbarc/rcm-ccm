import { ClaimSourceLineCells, ClaimSourceLineHeaders } from '@/components/claims/claim-source-line-columns';
import type { ClaimDetailLine } from '@/components/claims/detail-types';
import { EMPTY_VALUE, statusLabel, workStatusBackgroundStyle, workStatusBadgeStyle } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';

export function ClaimLinesDetailTable({ lines }: { lines: ClaimDetailLine[] }) {
    const workspace = useClaimWorkspace();

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[2210px] text-sm">
                <thead className="text-muted-foreground border-b text-left">
                    <tr>
                        <ClaimSourceLineHeaders />
                        {!workspace.isPrinciple && <th className="px-3 py-3 font-medium">Modifier</th>}
                        <th className="px-3 py-3 font-medium">Status</th>
                        <th className="px-3 py-3 font-medium">Denial Reason</th>
                        {!workspace.isPrinciple && <th className="px-3 py-3 text-right font-medium">Units</th>}
                        <th className="px-3 py-3 font-medium">Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    {lines.map((line) => (
                        <tr className="border-b last:border-0" key={line.id} style={workStatusBackgroundStyle(line.work_status_color)}>
                            <ClaimSourceLineCells line={line} />
                            {!workspace.isPrinciple && <td className="px-3 py-3">{line.modifier || EMPTY_VALUE}</td>}
                            <td className="px-3 py-3">
                                <Badge className="font-medium" style={workStatusBadgeStyle(line.work_status_color)} variant="outline">
                                    {line.work_status_label || statusLabel(line.work_status)}
                                </Badge>
                            </td>
                            <td className="text-muted-foreground max-w-64 px-3 py-3">
                                {line.denial_reason_label || line.denial_reason || EMPTY_VALUE}
                            </td>
                            {!workspace.isPrinciple && <td className="px-3 py-3 text-right tabular-nums">{line.units ?? EMPTY_VALUE}</td>}
                            <td className="px-3 py-3">
                                <div className="bg-background min-w-40 rounded-md border px-3 py-2">{line.assigned_to?.name ?? 'Unassigned'}</div>
                            </td>
                        </tr>
                    ))}
                    {lines.length === 0 && (
                        <tr>
                            <td className="text-muted-foreground p-10 text-center" colSpan={18}>
                                No claim lines found.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
