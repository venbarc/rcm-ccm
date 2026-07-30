import { ClaimEditDialog } from '@/components/claims/edit-dialog';
import { ClaimsExportDialog } from '@/components/claims/export-dialog';
import { ClaimsFilters } from '@/components/claims/filters';
import { ClaimsTable } from '@/components/claims/table';
import type { ClaimGroup, ClaimLine, ClaimPage, Filters, SortColumn, StatusOption, Summary, UserOption } from '@/components/claims/types';
import { currency } from '@/components/claims/utils';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, FileSpreadsheet, Users } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

const SEARCH_DEBOUNCE_MS = 350;

interface ClaimsIndexProps {
    claims: ClaimPage;
    filters: Filters;
    summary: Summary;
    workStatuses: StatusOption[];
    invoicedStatuses: StatusOption[];
    creditReasons: StatusOption[];
    assignees: UserOption[];
    hasActiveImport: boolean;
    canEditClaims: boolean;
}

export default function ClaimsIndex(props: ClaimsIndexProps) {
    return <ClaimsIndexContent {...props} key={JSON.stringify(props.filters)} />;
}

function ClaimsIndexContent({
    claims,
    filters,
    summary,
    workStatuses,
    invoicedStatuses,
    creditReasons,
    assignees,
    hasActiveImport,
    canEditClaims,
}: ClaimsIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const [local, setLocal] = useState(filters);
    const [expandedClaimId, setExpandedClaimId] = useState<number | null>(() => {
        const expanded = Number(filters.expanded);

        return Number.isFinite(expanded) && expanded > 0 ? expanded : null;
    });
    const [editingClaim, setEditingClaim] = useState<ClaimGroup | null>(null);
    const [editingLine, setEditingLine] = useState<ClaimLine | null>(null);
    const [exportOpen, setExportOpen] = useState(false);
    const [editForm, setEditForm] = useState({
        work_status: 'draft',
        denial_reason: '',
        notes: '',
        credit_status: '' as '' | 'yes' | 'no',
        credit_status_date: '',
        credit_reason: '',
    });
    const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const cancelPendingSearch = () => {
        if (searchTimer.current !== null) {
            clearTimeout(searchTimer.current);
            searchTimer.current = null;
        }
    };

    useEffect(
        () => () => {
            if (searchTimer.current !== null) {
                clearTimeout(searchTimer.current);
            }
        },
        [],
    );

    const visitFilters = (next: Filters) => {
        const expanded = new URL(window.location.href).searchParams.get('expanded') ?? '';

        router.get(
            '/claims',
            { ...next, expanded, page: undefined },
            {
                only: ['claims', 'filters', 'summary'],
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };
    const updateFilters = (values: Record<string, string>) => {
        const next = { ...local, ...values };
        const changed = Object.entries(values).some(([key, value]) => local[key] !== value);

        if (!changed) {
            return;
        }

        cancelPendingSearch();
        setLocal(next);
        visitFilters(next);
    };
    const updateSearch = (value: string) => {
        if (value === local.search) {
            return;
        }

        const next = { ...local, search: value };

        cancelPendingSearch();
        router.cancelAll({ async: false, prefetch: false, sync: true });
        setLocal(next);
        searchTimer.current = setTimeout(() => {
            searchTimer.current = null;
            visitFilters(next);
        }, SEARCH_DEBOUNCE_MS);
    };
    const apply = (values: Record<string, string> = {}) => {
        cancelPendingSearch();
        visitFilters({ ...local, ...values });
    };
    const clearFilters = () => {
        cancelPendingSearch();
        router.get(
            '/claims',
            {},
            {
                only: ['claims', 'filters', 'summary'],
                preserveScroll: true,
                replace: true,
            },
        );
    };
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
        if (!canEditClaims) {
            toast.error('You are not assigned to an administrator. Ask an administrator to add you as a member before editing claims.');

            return;
        }

        setEditingClaim(claim);
        setEditingLine(line);
        setEditForm({
            work_status: line.work_status || 'draft',
            denial_reason: line.denial_reason || '',
            notes: line.notes || '',
            credit_status: line.credit_status === null ? '' : line.credit_status ? 'yes' : 'no',
            credit_status_date: line.credit_status_date || '',
            credit_reason: line.credit_reason || '',
        });
    };
    const save = () => {
        if (!editingClaim || !editingLine) {
            return;
        }

        const editedClaimId = editingClaim.id;
        const editedLineId = editingLine.id;
        const workStatus = editForm.work_status;
        const denialReason = editForm.denial_reason.trim() || null;
        const notes = editForm.notes.trim() || null;
        const creditStatus = editForm.credit_status === '' ? null : editForm.credit_status === 'yes';
        const creditStatusDate = creditStatus === true ? editForm.credit_status_date || null : null;
        const creditReason = creditStatus === true ? editForm.credit_reason || null : null;
        const currentUser = {
            id: auth.user.id,
            name: auth.user.name,
            email: auth.user.email,
        };

        router.patch(
            `/claims/${editedLineId}?${buildStateQuery(editedClaimId)}`,
            {
                work_status: workStatus,
                denial_reason: denialReason,
                notes,
                credit_status: creditStatus,
                credit_status_date: creditStatusDate,
                credit_reason: creditReason,
            },
            {
                only: ['flash'],
                preserveUrl: true,
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    const updatedAt = new Date().toISOString();

                    router.replaceProp<ClaimsIndexProps>('claims.data', (currentData: unknown) =>
                        (currentData as ClaimGroup[]).map((claim) => {
                            if (claim.id !== editedClaimId) {
                                return claim;
                            }

                            const lines = claim.lines.map((line) =>
                                line.id === editedLineId
                                    ? {
                                          ...line,
                                          work_status: workStatus,
                                          denial_reason: denialReason,
                                          notes,
                                          credit_status: creditStatus,
                                          credit_status_date: creditStatusDate,
                                          credit_reason: creditReason,
                                          assigned_to: currentUser.id,
                                          assignee: currentUser,
                                          is_modified: workStatus !== 'draft' || denialReason !== null || notes !== null || creditStatus !== null,
                                          updated_at: updatedAt,
                                      }
                                    : line,
                            );
                            const creditedLine = lines.find((line) => line.credit_status === true);
                            const reviewedLine = creditedLine ?? lines.find((line) => line.credit_status === false);

                            return {
                                ...claim,
                                work_status: workStatus,
                                denial_reason: denialReason,
                                notes,
                                credit_status: reviewedLine?.credit_status ?? null,
                                credit_status_date: creditedLine?.credit_status_date ?? null,
                                credit_reason: creditedLine?.credit_reason ?? null,
                                assigned_to: currentUser.id,
                                assignee: currentUser,
                                modified_by: currentUser,
                                updated_at: updatedAt,
                                is_modified: lines.some((line) => line.is_modified),
                                lines,
                            };
                        }),
                    );
                    setEditingClaim(null);
                    setEditingLine(null);
                },
                onError: (errors) => {
                    const message = typeof errors.claim === 'string' ? errors.claim : 'The claim line could not be updated.';
                    toast.error(message);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Claims', href: '/claims' }]}>
            <Head title="Tricity Claims" />
            <div className="flex w-full max-w-full min-w-0 flex-1 flex-col gap-5 overflow-x-clip p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground mb-1 text-xs font-semibold tracking-[0.2em] uppercase">RCM workspace</p>
                        <h1 className="text-3xl font-semibold tracking-tight">Tricity Claims</h1>
                        <p className="text-muted-foreground text-sm">Billing inventory, work status, and follow-up notes in one queue.</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setExportOpen(true)}>
                            <Download /> Export
                        </Button>
                        {auth.user.is_admin && (
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
                        ['True Charge', currency(summary.totalTrueCharge)],
                        ['True Balance', currency(summary.totalTrueBalance)],
                        ['Payments', currency(summary.totalPayments)],
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
                    invoicedStatuses={invoicedStatuses}
                    local={local}
                    onClear={clearFilters}
                    onFilterChange={updateFilters}
                    onSearchChange={updateSearch}
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
                creditReasons={creditReasons}
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
            <ClaimsExportDialog
                assignees={assignees}
                canExportByAssignee={auth.user.is_admin}
                hasActiveImport={hasActiveImport}
                onOpenChange={setExportOpen}
                open={exportOpen}
                statuses={workStatuses}
            />
        </AppLayout>
    );
}
