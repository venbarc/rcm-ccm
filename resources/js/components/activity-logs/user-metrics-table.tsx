import type { ActivityFilters, PaginatedData, UserMetric } from '@/components/activity-logs/types';
import { formatCurrency, formatNumber } from '@/components/activity-logs/types';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';

interface UserMetricsTableProps {
    metrics: PaginatedData<UserMetric>;
    filters: ActivityFilters;
}

export function UserMetricsTable({ metrics, filters }: UserMetricsTableProps) {
    const workedLinesUrl = (userId: number) => {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => value && value !== 'all' && params.set(key, value));
        const returnTo = `/activity-logs${params.size > 0 ? `?${params}` : ''}`;

        return `/activity-logs/users/${userId}/worked-claim-lines?return_to=${encodeURIComponent(returnTo)}`;
    };

    return (
        <Card className="overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1000px] text-sm">
                    <thead className="bg-muted/50 border-b text-left">
                        <tr>
                            <th className="w-12 px-4 py-3 font-semibold">#</th>
                            <th className="min-w-52 px-4 py-3 font-semibold">User</th>
                            <th className="w-32 px-4 py-3 font-semibold">Role</th>
                            <th className="px-4 py-3 text-right font-semibold">Assigned</th>
                            <th className="px-4 py-3 text-right font-semibold">Worked/Closed</th>
                            <th className="px-4 py-3 text-right font-semibold">Balance</th>
                            <th className="px-4 py-3 text-right font-semibold">Closed Balance</th>
                            <th className="px-4 py-3 text-center font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {metrics.data.map((item, index) => (
                            <tr className="hover:bg-muted/30 border-b last:border-0" key={item.user_id}>
                                <td className="text-muted-foreground px-4 py-3">{(metrics.from ?? 1) + index}</td>
                                <td className="px-4 py-3">
                                    <p className="font-semibold">{item.name}</p>
                                    <p className="text-muted-foreground text-xs">{item.email}</p>
                                </td>
                                <td className="text-muted-foreground px-4 py-3 text-xs font-medium uppercase">{item.is_admin ? 'Admin' : 'User'}</td>
                                <td className="px-4 py-3 text-right font-semibold">{formatNumber(item.total_lines)}</td>
                                <td className="px-4 py-3 text-right font-semibold">
                                    {formatNumber(item.worked_lines)}/{formatNumber(item.closed_lines)}
                                </td>
                                <td className="px-4 py-3 text-right font-semibold text-rose-600">{formatCurrency(item.total_balance)}</td>
                                <td className="px-4 py-3 text-right font-semibold text-emerald-600">{formatCurrency(item.closed_balance)}</td>
                                <td className="px-4 py-3 text-center">
                                    {item.worked_lines > 0 ? (
                                        <Button asChild size="sm" variant="outline">
                                            <Link href={workedLinesUrl(item.user_id)}>
                                                <Eye />
                                                View
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button disabled size="sm" variant="outline">
                                            <Eye />
                                            View
                                        </Button>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {metrics.data.length === 0 && (
                            <tr>
                                <td className="text-muted-foreground p-12 text-center" colSpan={8}>
                                    No activity found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            {metrics.data.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-t p-4">
                    <p className="text-muted-foreground text-sm">
                        Showing <span className="font-medium">{metrics.from}</span> to <span className="font-medium">{metrics.to}</span> of{' '}
                        <span className="font-medium">{metrics.total}</span> users
                    </p>
                    <Pagination links={metrics.links} />
                </div>
            )}
        </Card>
    );
}
