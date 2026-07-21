import { StatusDetailsDialog } from '@/components/activity-logs/status-details-dialog';
import { ActivityStatusSummary } from '@/components/activity-logs/status-summary';
import type { ActivityFilters, PaginatedData, StatusSummaryItem, UserMetric, WorkedLine } from '@/components/activity-logs/types';
import { UserMetricsTable } from '@/components/activity-logs/user-metrics-table';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface ActivityLogsProps {
    metrics: PaginatedData<UserMetric>;
    statusSummary: StatusSummaryItem[];
    filters: ActivityFilters;
    roleOptions: { value: string; label: string }[];
    isAdmin: boolean;
}

export default function ActivityLogs({ metrics, statusSummary, filters, roleOptions, isAdmin }: ActivityLogsProps) {
    const isLoading = useInertiaLoading();
    const [local, setLocal] = useState(filters);
    const [selectedStatus, setSelectedStatus] = useState<StatusSummaryItem | null>(null);
    const [statusLines, setStatusLines] = useState<WorkedLine[]>([]);
    const [statusPage, setStatusPage] = useState(1);
    const [statusHasMore, setStatusHasMore] = useState(false);
    const [statusLoading, setStatusLoading] = useState(false);
    const asParams = (values: ActivityFilters): Record<string, string> => ({
        search: values.search,
        role: values.role,
        worked_from: values.worked_from,
        worked_to: values.worked_to,
    });
    const currentActivityUrl = `/activity-logs?${new URLSearchParams(asParams(filters)).toString()}`;

    const applyFilters = (next = local) => router.get('/activity-logs', asParams(next), { preserveState: true, replace: true });
    const clearFilters = () => {
        const cleared = { search: '', role: 'all', worked_from: '', worked_to: '' };
        setLocal(cleared);
        applyFilters(cleared);
    };
    const loadStatus = async (status: StatusSummaryItem, page = 1) => {
        setSelectedStatus(status);
        setStatusLoading(true);
        try {
            const params = new URLSearchParams({ ...filters, status: status.status, page: String(page) });
            const response = await fetch(`/activity-logs/status-details?${params}`);
            if (!response.ok) return;
            const payload = (await response.json()) as { data: WorkedLine[]; current_page: number; has_more: boolean };
            setStatusLines((current) => (page === 1 ? payload.data : [...current, ...payload.data]));
            setStatusPage(payload.current_page);
            setStatusHasMore(payload.has_more);
        } finally {
            setStatusLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Activity Logs', href: '/activity-logs' }]}>
            <Head title="Activity Logs" />
            <DataLoadingOverlay className="flex-1" isLoading={isLoading} label="Loading activity logs...">
                <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Activity Logs</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Monitor worked and closed claim lines by assignee.</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Filters</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap items-end gap-4">
                                <div className="flex min-w-60 flex-1 flex-col gap-2">
                                    <Label htmlFor="activity-search">Search</Label>
                                    <SearchInput
                                        id="activity-search"
                                        onChange={(event) => setLocal({ ...local, search: event.target.value })}
                                        onKeyDown={(event) => event.key === 'Enter' && applyFilters()}
                                        placeholder="Search by name or email"
                                        value={local.search}
                                    />
                                </div>
                                {isAdmin && (
                                    <div className="flex min-w-48 flex-1 flex-col gap-2">
                                        <Label htmlFor="activity-role">Role</Label>
                                        <Select
                                            value={local.role || 'all'}
                                            onValueChange={(role) => {
                                                const next = { ...local, role };
                                                setLocal(next);
                                                applyFilters(next);
                                            }}
                                        >
                                            <SelectTrigger id="activity-role">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All roles</SelectItem>
                                                {roleOptions.map((role) => (
                                                    <SelectItem key={role.value} value={role.value}>
                                                        {role.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                                <DateRangeFilterField
                                    className="min-w-72 flex-[2]"
                                    from={local.worked_from}
                                    label="Worked Date Range"
                                    onApply={({ from, to }) => {
                                        const next = { ...local, worked_from: from, worked_to: to };
                                        setLocal(next);
                                        applyFilters(next);
                                    }}
                                    placeholder="Select worked date range"
                                    to={local.worked_to}
                                />
                                <div className="flex gap-2">
                                    <Button variant="outline" onClick={() => applyFilters()}>
                                        Apply
                                    </Button>
                                    <Button variant="ghost" onClick={clearFilters}>
                                        Clear
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <ActivityStatusSummary
                        description="Totals across filtered members."
                        exportHref={`/activity-logs/export?${new URLSearchParams(asParams(filters))}`}
                        statuses={statusSummary}
                        onSelect={(status) => void loadStatus(status)}
                    />
                    <UserMetricsTable filters={filters} metrics={metrics} />
                </div>
            </DataLoadingOverlay>

            <StatusDetailsDialog
                hasMore={statusHasMore}
                isLoading={statusLoading}
                lines={statusLines}
                onClose={() => setSelectedStatus(null)}
                onLoadMore={() => selectedStatus && void loadStatus(selectedStatus, statusPage + 1)}
                returnTo={currentActivityUrl}
                status={selectedStatus}
            />
        </AppLayout>
    );
}
