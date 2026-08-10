import { ClaimActivityTimeline } from '@/components/claims/claim-activity-timeline';
import { ClaimLinesDetailTable } from '@/components/claims/claim-lines-detail-table';
import type { ClaimDetail, ClaimDetailActivity } from '@/components/claims/detail-types';
import { currency, date, serviceDateRange, statusLabel, workStatusBadgeStyle } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Activity, ArrowLeft, StickyNote } from 'lucide-react';
import { useState } from 'react';

interface ClaimShowProps {
    claim: ClaimDetail;
    activities: ClaimDetailActivity[];
    activitiesPage: number;
    activitiesHasMore: boolean;
    returnTo: string;
}

const DetailRow = ({ label, value }: { label: string; value: React.ReactNode }) => (
    <div className="flex items-start justify-between gap-4">
        <span className="text-muted-foreground">{label}:</span>
        <span className="text-right font-medium">{value}</span>
    </div>
);

export default function ClaimShow({ claim, activities, activitiesPage, activitiesHasMore, returnTo }: ClaimShowProps) {
    const workspace = useClaimWorkspace();
    const [activityItems, setActivityItems] = useState(activities);
    const [activityPage, setActivityPage] = useState(activitiesPage);
    const [hasMoreActivities, setHasMoreActivities] = useState(activitiesHasMore);
    const [isLoadingActivities, setIsLoadingActivities] = useState(false);
    const statuses = [
        ...new Map(
            claim.lines.map((line) => [line.work_status, { value: line.work_status, label: line.work_status_label, color: line.work_status_color }]),
        ).values(),
    ];
    const notes = claim.lines.filter((line) => line.notes);

    const loadMoreActivities = async () => {
        if (!hasMoreActivities || isLoadingActivities) return;

        setIsLoadingActivities(true);
        try {
            const response = await fetch(`/claims/${claim.id}/activities?page=${activityPage + 1}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;

            const payload = (await response.json()) as { data: ClaimDetailActivity[]; current_page: number; has_more: boolean };
            setActivityItems((current) => [...current, ...payload.data]);
            setActivityPage(payload.current_page);
            setHasMoreActivities(payload.has_more);
        } finally {
            setIsLoadingActivities(false);
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Claims', href: returnTo },
                { title: claim.bill_id, href: `/claims/${claim.id}` },
            ]}
        >
            <Head title={`${workspace.identifierLabel} ${claim.bill_id}`} />
            <div className="flex flex-1 flex-col gap-4 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button asChild size="icon" variant="ghost">
                            <Link aria-label="Back to claims" href={returnTo}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">
                                {workspace.identifierLabel} {claim.bill_id}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                {claim.line_count} {workspace.procedureLabel} line{claim.line_count === 1 ? '' : 's'}
                            </p>
                        </div>
                        {statuses.map((status) => (
                            <Badge className="font-medium" key={status.value} style={workStatusBadgeStyle(status.color)} variant="outline">
                                {status.label || statusLabel(status.value)}
                            </Badge>
                        ))}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>{workspace.patientLabel}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1">
                            <p className="font-medium">{claim.patient_name || '-'}</p>
                            <p className="text-muted-foreground text-sm">
                                {workspace.patientIdLabel}: {claim.patient_id || '-'}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {workspace.patientDobLabel}: {date(claim.patient_dob)}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>{workspace.locationLabel}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="font-medium">{claim.facility || '-'}</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Claim Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm">
                            <DetailRow
                                label={workspace.serviceDateLabel}
                                value={serviceDateRange(claim.service_date_start, claim.service_date_end)}
                            />
                            {!workspace.isPrinciple && <DetailRow label="Service Type" value={claim.service_type || '-'} />}
                            {!workspace.isPrinciple && <DetailRow label="Dx Codes" value={claim.diagnosis_codes.join(', ') || '-'} />}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Financial Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm">
                            <DetailRow label="True Charge" value={currency(claim.total_true_charge)} />
                            <DetailRow
                                label={workspace.paymentsLabel}
                                value={<span className="text-green-600">{currency(claim.total_payments)}</span>}
                            />
                            {workspace.showTrueBalance && (
                                <DetailRow
                                    label="True Balance"
                                    value={<span className="text-orange-600">{currency(claim.total_true_balance)}</span>}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Claim Lines</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ClaimLinesDetailTable lines={claim.lines} />
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-[2fr_1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="size-5" />
                                Activity Log
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ClaimActivityTimeline
                                activities={activityItems}
                                hasMore={hasMoreActivities}
                                isLoading={isLoadingActivities}
                                onLoadMore={() => void loadMoreActivities()}
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <StickyNote className="size-5" />
                                Claim Notes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {notes.length === 0 ? (
                                <p className="text-muted-foreground py-8 text-center text-sm">No claim notes yet.</p>
                            ) : (
                                <div className="space-y-3">
                                    {notes.map((line) => (
                                        <div className="bg-muted/20 rounded-md border p-3 text-sm" key={line.id}>
                                            <Badge className="mb-2" variant="secondary">
                                                {workspace.procedureLabel} {line.cpt_code || '-'}
                                            </Badge>
                                            <p className="whitespace-pre-wrap">{line.notes}</p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
