import { ClaimsByStatusCard } from '@/components/dashboard/claims-by-status-card';
import { RadialMetricCard } from '@/components/dashboard/radial-metric-card';
import { DashboardRangeFilter } from '@/components/dashboard/range-filter';
import type { ClaimByStatus, DashboardFilters, WorkSummary } from '@/components/dashboard/types';
import { formatCount } from '@/components/dashboard/types';
import { WorkSummaryTable } from '@/components/dashboard/work-summary-table';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface DashboardProps {
    accountLabel: string;
    filters: DashboardFilters;
    workSummary: WorkSummary;
    claimsByStatus: ClaimByStatus[];
}

export default function Dashboard({ accountLabel, filters, workSummary, claimsByStatus }: DashboardProps) {
    const isLoading = useInertiaLoading();
    const workedLinePercent = workSummary.totalCount > 0 ? Math.min((workSummary.workedCount / workSummary.totalCount) * 100, 100) : 0;
    const paidLinePercent = workSummary.totalCount > 0 ? Math.min((workSummary.paidCount / workSummary.totalCount) * 100, 100) : 0;

    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            <Head title="Dashboard" />
            <DataLoadingOverlay isLoading={isLoading} label="Updating dashboard..." className="flex-1">
                <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
                            <p className="text-muted-foreground mt-1 text-sm">Claims performance and collection progress</p>
                        </div>
                        <Badge variant="outline">{accountLabel}</Badge>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-xl">Work Summary</CardTitle>
                            <CardDescription>
                                {filters.presetLabel === filters.label ? filters.label : `${filters.presetLabel} - ${filters.label}`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <DashboardRangeFilter key={`${filters.preset}-${filters.start}-${filters.end}`} filters={filters} />
                            <div className="border-muted border-b" />
                            <WorkSummaryTable summary={workSummary} />
                        </CardContent>
                    </Card>

                    <ClaimsByStatusCard statuses={claimsByStatus} />

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-xl">Operations Overview</CardTitle>
                            <CardDescription>Line completion</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <RadialMetricCard
                                    label="Worked"
                                    value={`${formatCount(workSummary.workedCount)} / ${formatCount(workSummary.totalCount)}`}
                                    percentage={workedLinePercent}
                                    colorClass="text-blue-500"
                                    helperText="Worked / total CPT lines"
                                />
                                <RadialMetricCard
                                    label="Paid"
                                    value={`${formatCount(workSummary.paidCount)} / ${formatCount(workSummary.totalCount)}`}
                                    percentage={paidLinePercent}
                                    colorClass="text-emerald-500"
                                    helperText="CPT lines fully paid"
                                />
                            </div>
                            <div className="border-muted/60 bg-muted/20 rounded-md border p-3 text-xs">
                                <div className="text-muted-foreground flex justify-between gap-3">
                                    <span>Remaining lines</span>
                                    <span className="text-foreground font-semibold">{formatCount(workSummary.remainingCount)}</span>
                                </div>
                                <div className="text-muted-foreground mt-2 flex justify-between gap-3">
                                    <span>Claimed progress</span>
                                    <span className="text-foreground font-medium">{workSummary.workedProgress.toFixed(2)}%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </DataLoadingOverlay>
        </AppLayout>
    );
}
