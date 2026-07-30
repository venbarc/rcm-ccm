import { ClaimSourceLineCells, ClaimSourceLineHeaders } from '@/components/claims/claim-source-line-columns';
import type { ClaimDetailLine } from '@/components/claims/detail-types';
import { EMPTY_VALUE, statusClass, statusLabel, statusRowClass } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';

export function ClaimLinesDetailTable({ lines }: { lines: ClaimDetailLine[] }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[1950px] text-sm">
                <thead className="text-muted-foreground border-b text-left">
                    <tr>
                        <ClaimSourceLineHeaders />
                        <th className="px-3 py-3 font-medium">Modifier</th>
                        <th className="px-3 py-3 font-medium">Status</th>
                        <th className="px-3 py-3 font-medium">Denial Reason</th>
                        <th className="px-3 py-3 text-right font-medium">Units</th>
                        <th className="px-3 py-3 font-medium">Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    {lines.map((line) => (
                        <tr className={`border-b last:border-0 ${statusRowClass[line.work_status] ?? statusRowClass.draft}`} key={line.id}>
                            <ClaimSourceLineCells line={line} />
                            <td className="px-3 py-3">{line.modifier || EMPTY_VALUE}</td>
                            <td className="px-3 py-3">
                                <Badge className={statusClass[line.work_status] ?? statusClass.draft} variant="outline">
                                    {statusLabel(line.work_status)}
                                </Badge>
                            </td>
                            <td className="text-muted-foreground max-w-64 px-3 py-3">{line.denial_reason || EMPTY_VALUE}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{line.units ?? EMPTY_VALUE}</td>
                            <td className="px-3 py-3">
                                <div className="bg-background min-w-40 rounded-md border px-3 py-2">{line.assigned_to?.name ?? 'Unassigned'}</div>
                            </td>
                        </tr>
                    ))}
                    {lines.length === 0 && (
                        <tr>
                            <td className="text-muted-foreground p-10 text-center" colSpan={16}>
                                No claim lines found.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
