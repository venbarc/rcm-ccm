import type { ClaimDetailLine } from '@/components/claims/detail-types';
import { EMPTY_VALUE, currency, statusClass, statusLabel, statusRowClass } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';

const priorityClass = (priority: string) => {
    const normalized = priority.toLowerCase();
    if (normalized === 'urgent') return 'border-red-300 bg-red-50 text-red-700';
    if (normalized === 'high') return 'border-orange-300 bg-orange-50 text-orange-700';
    if (normalized === 'medium') return 'border-blue-300 bg-blue-50 text-blue-700';

    return 'border-slate-300 bg-slate-50 text-slate-700';
};

export function ClaimLinesDetailTable({ lines }: { lines: ClaimDetailLine[] }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[1500px] text-sm">
                <thead className="text-muted-foreground border-b text-left">
                    <tr>
                        <th className="px-3 py-3 font-medium">CPT</th>
                        <th className="px-3 py-3 font-medium">Modifier</th>
                        <th className="px-3 py-3 font-medium">Status</th>
                        <th className="px-3 py-3 font-medium">Priority</th>
                        <th className="px-3 py-3 font-medium">Denial Reason</th>
                        <th className="px-3 py-3 font-medium">Rendering Provider</th>
                        <th className="px-3 py-3 font-medium">Primary Payer</th>
                        <th className="px-3 py-3 text-right font-medium">Units</th>
                        <th className="px-3 py-3 text-right font-medium">Charges</th>
                        <th className="px-3 py-3 text-right font-medium">Paid</th>
                        <th className="px-3 py-3 text-right font-medium">Adj</th>
                        <th className="px-3 py-3 text-right font-medium">Balance</th>
                        <th className="px-3 py-3 font-medium">Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    {lines.map((line) => (
                        <tr className={`border-b last:border-0 ${statusRowClass[line.work_status] ?? statusRowClass.draft}`} key={line.id}>
                            <td className="px-3 py-3 font-semibold">{line.cpt_code || EMPTY_VALUE}</td>
                            <td className="px-3 py-3">{line.modifier || EMPTY_VALUE}</td>
                            <td className="px-3 py-3">
                                <Badge className={statusClass[line.work_status] ?? statusClass.draft} variant="outline">
                                    {statusLabel(line.work_status)}
                                </Badge>
                            </td>
                            <td className="px-3 py-3">
                                {line.priority ? (
                                    <Badge className={priorityClass(line.priority)} variant="outline">
                                        {line.priority}
                                    </Badge>
                                ) : (
                                    EMPTY_VALUE
                                )}
                            </td>
                            <td className="text-muted-foreground max-w-64 px-3 py-3">{line.denial_reason || EMPTY_VALUE}</td>
                            <td className="px-3 py-3">{line.rendering_provider || EMPTY_VALUE}</td>
                            <td className="px-3 py-3">{line.payer_name || EMPTY_VALUE}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{line.units ?? EMPTY_VALUE}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{currency(line.charges)}</td>
                            <td className="px-3 py-3 text-right font-medium text-green-600 tabular-nums">{currency(line.paid)}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{currency(line.adjustments)}</td>
                            <td className="px-3 py-3 text-right font-medium text-orange-600 tabular-nums">{currency(line.balance)}</td>
                            <td className="px-3 py-3">
                                <div className="bg-background min-w-40 rounded-md border px-3 py-2">{line.assigned_to?.name ?? 'Unassigned'}</div>
                            </td>
                        </tr>
                    ))}
                    {lines.length === 0 && (
                        <tr>
                            <td className="text-muted-foreground p-10 text-center" colSpan={13}>
                                No claim lines found.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
