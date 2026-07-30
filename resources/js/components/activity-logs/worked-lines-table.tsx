import type { PaginatedData, WorkedLine } from '@/components/activity-logs/types';
import { formatCurrency } from '@/components/activity-logs/types';
import { date, statusLabel, workStatusBadgeStyle } from '@/components/claims/utils';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';

export function WorkedLinesTable({ lines, returnTo }: { lines: PaginatedData<WorkedLine>; returnTo: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Worked Bill Lines</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[1100px] text-sm">
                        <thead className="bg-muted/50 border-b text-left">
                            <tr>
                                <th className="px-3 py-3 font-semibold">Bill ID</th>
                                <th className="px-3 py-3 font-semibold">Patient</th>
                                <th className="px-3 py-3 font-semibold">CPT</th>
                                <th className="px-3 py-3 font-semibold">Status</th>
                                <th className="px-3 py-3 font-semibold">Service Date</th>
                                <th className="px-3 py-3 font-semibold">Last Worked</th>
                                <th className="px-3 py-3 text-right font-semibold">True Charge</th>
                                <th className="px-3 py-3 text-right font-semibold">Payments</th>
                                <th className="px-3 py-3 text-right font-semibold">True Balance</th>
                                <th className="px-3 py-3 text-center font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {lines.data.map((line) => (
                                <tr className="hover:bg-muted/30 border-b last:border-0" key={line.id}>
                                    <td className="text-primary px-3 py-3 font-medium">{line.claim_number}</td>
                                    <td className="px-3 py-3">{line.patient_name || '-'}</td>
                                    <td className="px-3 py-3 font-medium">{line.cpt_code || '-'}</td>
                                    <td className="px-3 py-3">
                                        <Badge className="font-medium" style={workStatusBadgeStyle(line.status_color)} variant="outline">
                                            {line.status_label || statusLabel(line.status)}
                                        </Badge>
                                    </td>
                                    <td className="px-3 py-3">{date(line.date_of_service)}</td>
                                    <td className="px-3 py-3">{line.worked_at ? new Date(line.worked_at).toLocaleString() : '-'}</td>
                                    <td className="px-3 py-3 text-right">{formatCurrency(line.charges)}</td>
                                    <td className="px-3 py-3 text-right text-green-600">{formatCurrency(line.paid)}</td>
                                    <td className="px-3 py-3 text-right font-semibold text-rose-600">{formatCurrency(line.balance)}</td>
                                    <td className="px-3 py-3 text-center">
                                        <Button asChild size="icon" variant="ghost">
                                            <Link
                                                aria-label={`View Bill ID ${line.claim_number}`}
                                                href={`/claims/${line.claim_id}?return_to=${encodeURIComponent(returnTo)}`}
                                            >
                                                <Eye />
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {lines.data.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground p-12 text-center" colSpan={10}>
                                        No worked claim lines found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {lines.data.length > 0 && (
                    <div className="mt-4">
                        <Pagination links={lines.links} />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
