import { ClaimsByStatusCard } from '@/components/dashboard/claims-by-status-card';
import { CreditStatusSummaryCard } from '@/components/dashboard/credit-status-summary-card';
import { FinancialSummaryCard } from '@/components/dashboard/financial-summary-card';
import { InvoicedSummaryCard } from '@/components/dashboard/invoiced-summary-card';
import { RadialMetricCard } from '@/components/dashboard/radial-metric-card';
import { DashboardRangeFilter } from '@/components/dashboard/range-filter';
import { SummaryDateFilters } from '@/components/dashboard/summary-date-filters';
import type {
    ClaimByStatus,
    DashboardCreditStatusSummary,
    DashboardFilters,
    DashboardFinancialSummary,
    DashboardInvoicedSummary,
    DashboardPanelFilters,
    WorkSummary,
} from '@/components/dashboard/types';
import { formatCount } from '@/components/dashboard/types';
import { WorkSummaryTable } from '@/components/dashboard/work-summary-table';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface DashboardProps {
    accountLabel: string;
    filters: DashboardFilters;
    panelFilters: DashboardPanelFilters;
    workSummary: WorkSummary;
    claimsByStatus: ClaimByStatus[];
    cptSummary?: DashboardFinancialSummary;
    modmedStatusSummary?: DashboardFinancialSummary;
    invoicedSummary?: DashboardInvoicedSummary;
    creditStatusSummary?: DashboardCreditStatusSummary;
}

export default function Dashboard({
    accountLabel,
    filters,
    panelFilters,
    workSummary,
    claimsByStatus,
    cptSummary,
    modmedStatusSummary,
    invoicedSummary,
    creditStatusSummary,
}: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const isLoading = useInertiaLoading();
    const workedLinePercent = workSummary.totalCount > 0 ? Math.min((workSummary.workedCount / workSummary.totalCount) * 100, 100) : 0;
    const paidLinePercent = workSummary.totalCount > 0 ? Math.min((workSummary.paidCount / workSummary.totalCount) * 100, 100) : 0;
    const showAdminSummaries = auth.user.is_admin && cptSummary && modmedStatusSummary && invoicedSummary && creditStatusSummary;

    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            <Head title="Dashboard" />
            <DataLoadingOverlay isLoading={isLoading} label="Updating dashboard..." className="flex-1">
                <div className="flex w-full max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
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

                    {showAdminSummaries && (
                        <section aria-label="Admin financial summaries" className="flex min-w-0 flex-col gap-6">
                            <FinancialSummaryCard
                                description="CPT-level volume, collections, balances, and CF invoice totals for the selected range."
                                filters={<SummaryDateFilters filters={panelFilters.cptSummary} prefix="cpt" />}
                                groupHeading="CPT"
                                groupKind="cpt"
                                summary={cptSummary}
                                title="Summary by CPT Codes"
                            />
                            <FinancialSummaryCard
                                description="Financial performance grouped by the imported ModMed Claim Status."
                                filters={<SummaryDateFilters filters={panelFilters.modmedStatusSummary} prefix="modmed" />}
                                groupHeading="ModMed Claim Status"
                                groupKind="modmed-status"
                                summary={modmedStatusSummary}
                                title="Summary by Claim Status"
                            />
                            <div className="grid min-w-0 gap-6 lg:grid-cols-2">
                                <InvoicedSummaryCard
                                    filters={<SummaryDateFilters filters={panelFilters.invoicedSummary} prefix="invoiced" showServiceDate={false} />}
                                    summary={invoicedSummary}
                                />
                                <CreditStatusSummaryCard
                                    filters={
                                        <SummaryDateFilters
                                            filters={panelFilters.creditStatusSummary}
                                            invoiceDateLabel="Credit Status Date Range"
                                            invoiceDatePlaceholder="Select credit status date range"
                                            prefix="credit_status"
                                            showServiceDate={false}
                                        />
                                    }
                                    summary={creditStatusSummary}
                                />
                            </div>
                        </section>
                    )}

                    <ClaimsByStatusCard
                        filters={<SummaryDateFilters filters={panelFilters.claimsByStatus} prefix="claims_status" />}
                        statuses={claimsByStatus}
                    />

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
