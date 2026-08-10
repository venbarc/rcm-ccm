import { ActivityStatusSummary } from '@/components/activity-logs/status-summary';
import type { PaginatedData, StatusSummaryItem, WorkedLine } from '@/components/activity-logs/types';
import { WorkedLinesTable } from '@/components/activity-logs/worked-lines-table';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

interface WorkedFilters {
    claim_number: string;
    cpt_code: string;
    status: string;
    date_filter_type: string;
    date_from: string;
    date_to: string;
}

export default function WorkedClaimLines({
    user,
    workedStatusSummary,
    statusOptions,
    workedLines,
    filters,
    returnTo,
}: {
    user: { id: number; name: string; email: string; is_admin: boolean };
    workedStatusSummary: StatusSummaryItem[];
    statusOptions: { value: string; label: string }[];
    workedLines: PaginatedData<WorkedLine>;
    filters: WorkedFilters;
    returnTo: string;
}) {
    const isLoading = useInertiaLoading();
    const workspace = useClaimWorkspace();
    const [local, setLocal] = useState(filters);
    const path = `/activity-logs/users/${user.id}/worked-claim-lines`;
    const workedReturnTo = `${path}?${new URLSearchParams({ ...filters, return_to: returnTo }).toString()}`;
    const exportHref = `/activity-logs/export?${new URLSearchParams({ ...filters, user_id: String(user.id) }).toString()}`;
    const apply = (next = local) => router.get(path, { ...next, return_to: returnTo }, { preserveState: true, replace: true });
    const clear = () => {
        const next = { claim_number: '', cpt_code: '', status: 'all', date_filter_type: 'date_of_service', date_from: '', date_to: '' };
        setLocal(next);
        apply(next);
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Activity Logs', href: returnTo },
                { title: 'Worked Claim Lines', href: path },
            ]}
        >
            <Head title="Worked Claim Lines" />
            <DataLoadingOverlay className="flex-1" isLoading={isLoading} label="Loading worked lines...">
                <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Worked Claim Lines</h1>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {user.name}
                                {user.email ? ` - ${user.email}` : ''}
                            </p>
                        </div>
                        <Button asChild variant="outline">
                            <Link href={returnTo}>
                                <ArrowLeft />
                                Back to Activity Logs
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent className="space-y-4 p-6">
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h2 className="text-sm font-semibold">Filters</h2>
                                    <p className="text-muted-foreground text-xs">Applies to status summary and worked claim lines.</p>
                                </div>
                                <div className="flex gap-2">
                                    <Button onClick={() => apply()}>Apply filters</Button>
                                    <Button variant="ghost" onClick={clear}>
                                        Clear all
                                    </Button>
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="claim-number">{workspace.identifierLabel}</Label>
                                    <SearchInput
                                        id="claim-number"
                                        value={local.claim_number}
                                        onChange={(event) => setLocal({ ...local, claim_number: event.target.value })}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="cpt-code">{workspace.procedureLabel}</Label>
                                    <Input
                                        id="cpt-code"
                                        value={local.cpt_code}
                                        onChange={(event) => setLocal({ ...local, cpt_code: event.target.value })}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label>Status</Label>
                                    <Select value={local.status || 'all'} onValueChange={(status) => setLocal({ ...local, status })}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All statuses</SelectItem>
                                            {statusOptions.map((status) => (
                                                <SelectItem key={status.value} value={status.value}>
                                                    {status.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="flex flex-col gap-2">
                                    <Label>Date Filter</Label>
                                    <Select
                                        value={local.date_filter_type}
                                        onValueChange={(date_filter_type) => setLocal({ ...local, date_filter_type })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="date_of_service">Date of Service</SelectItem>
                                            <SelectItem value="last_worked">Last Worked</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <DateRangeFilterField
                                    from={local.date_from}
                                    label="Date Range"
                                    onApply={({ from, to }) => {
                                        const next = { ...local, date_from: from, date_to: to };
                                        setLocal(next);
                                        apply(next);
                                    }}
                                    placeholder="Select date range"
                                    to={local.date_to}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <ActivityStatusSummary
                        description="Click a card to filter worked claim lines."
                        exportHref={exportHref}
                        statuses={workedStatusSummary}
                        onSelect={(status) => {
                            const next = { ...local, status: status.status };
                            setLocal(next);
                            apply(next);
                        }}
                    />
                    <WorkedLinesTable lines={workedLines} returnTo={workedReturnTo} />
                </div>
            </DataLoadingOverlay>
        </AppLayout>
    );
}
