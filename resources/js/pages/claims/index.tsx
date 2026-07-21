import { ClaimEditDialog } from '@/components/claims/edit-dialog';
import { ClaimsFilters } from '@/components/claims/filters';
import { ClaimsTable } from '@/components/claims/table';
import type { ClaimGroup, ClaimLine, ClaimPage, Filters, SortColumn, StatusOption, Summary, UserOption } from '@/components/claims/types';
import { currency } from '@/components/claims/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileSpreadsheet, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface ClaimsIndexProps {
    claims: ClaimPage;
    filters: Filters;
    summary: Summary;
    workStatuses: StatusOption[];
    assignees: UserOption[];
}

export default function ClaimsIndex(props: ClaimsIndexProps) {
    return <ClaimsIndexContent {...props} key={JSON.stringify(props.filters)} />;
}

function ClaimsIndexContent({ claims, filters, summary, workStatuses, assignees }: ClaimsIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const [local, setLocal] = useState(filters);
    const [expandedClaimId, setExpandedClaimId] = useState<number | null>(() => {
        const expanded = Number(filters.expanded);

        return Number.isFinite(expanded) && expanded > 0 ? expanded : null;
    });
    const [editingClaim, setEditingClaim] = useState<ClaimGroup | null>(null);
    const [editingLine, setEditingLine] = useState<ClaimLine | null>(null);
    const [editForm, setEditForm] = useState({ work_status: 'draft', denial_reason: '', notes: '' });

    const apply = (values: Partial<Filters> = {}) =>
        router.get('/claims', { ...local, ...values, page: undefined }, { preserveState: true, replace: true });
    const submitFilters = (event: FormEvent) => {
        event.preventDefault();
        apply();
    };
    const clearFilters = () => router.get('/claims');
    const sort = (column: SortColumn) =>
        apply({ sort_by: column, sort_direction: filters.sort_by === column && filters.sort_direction === 'asc' ? 'desc' : 'asc' });
    const toggleClaim = (id: number) => {
        const nextExpandedId = expandedClaimId === id ? null : id;
        setExpandedClaimId(nextExpandedId);
        setLocal((current) => ({ ...current, expanded: nextExpandedId ? String(nextExpandedId) : '' }));

        const url = new URL(window.location.href);
        if (nextExpandedId) {
            url.searchParams.set('expanded', String(nextExpandedId));
        } else {
            url.searchParams.delete('expanded');
        }
        window.history.replaceState(window.history.state, '', url);
    };
    const buildStateQuery = (expandedId: number) => {
        const params = new URLSearchParams(window.location.search);
        Object.entries(filters).forEach(([key, value]) => {
            if (value && key !== 'expanded') {
                params.set(key, value);
            }
        });
        params.set('expanded', String(expandedId));
        params.set('page', String(claims.current_page));

        return params.toString();
    };
    const openEditLine = (claim: ClaimGroup, line: ClaimLine) => {
        setEditingClaim(claim);
        setEditingLine(line);
        setEditForm({
            work_status: line.work_status || 'draft',
            denial_reason: line.denial_reason || '',
            notes: line.notes || '',
        });
    };
    const save = () => {
        if (!editingLine) {
            return;
        }

        router.patch(
            `/claims/${editingLine.id}?${buildStateQuery(editingClaim?.id ?? editingLine.id)}`,
            {
                work_status: editForm.work_status,
                denial_reason: editForm.denial_reason || null,
                notes: editForm.notes || null,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setEditingClaim(null);
                    setEditingLine(null);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Claims', href: '/claims' }]}>
            <Head title="Tricity Claims" />
            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground mb-1 text-xs font-semibold tracking-[0.2em] uppercase">RCM workspace</p>
                        <h1 className="text-3xl font-semibold tracking-tight">Tricity Claims</h1>
                        <p className="text-muted-foreground text-sm">Billing inventory, work status, and follow-up notes in one queue.</p>
                    </div>
                    <div className="flex gap-2">
                        {(auth.user.is_admin || auth.user.can_assign_claims) && (
                            <Button variant="outline" asChild>
                                <Link href="/assignments">
                                    <Users /> Distribute claims
                                </Link>
                            </Button>
                        )}
                        {auth.user.is_admin && (
                            <Button asChild>
                                <Link href="/claims-import">
                                    <FileSpreadsheet /> Import
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        ['Total claims', summary.totalCount.toLocaleString()],
                        ['True charges', currency(summary.totalTrueCharge)],
                        ['True balance', currency(summary.totalTrueBalance)],
                        ['Posted payments', currency(summary.totalPayments)],
                    ].map(([label, value], index) => (
                        <Card className={index === 0 ? 'border-l-4 border-l-sky-500' : ''} key={label}>
                            <CardContent className="p-4">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <ClaimsFilters
                    assignees={assignees}
                    local={local}
                    onClear={clearFilters}
                    onSubmit={submitFilters}
                    setLocal={setLocal}
                    workStatuses={workStatuses}
                />
                <ClaimsTable
                    claims={claims}
                    expandedClaimId={expandedClaimId}
                    filters={filters}
                    onEditLine={openEditLine}
                    onSort={sort}
                    onToggleClaim={toggleClaim}
                />
            </div>

            <ClaimEditDialog
                claim={editingClaim}
                editForm={editForm}
                line={editingLine}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingClaim(null);
                        setEditingLine(null);
                    }
                }}
                onSave={save}
                open={editingLine !== null}
                setEditForm={setEditForm}
                workStatuses={workStatuses}
            />
        </AppLayout>
    );
}
