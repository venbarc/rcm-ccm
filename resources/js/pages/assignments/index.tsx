import { AssignedWorkloadTable } from '@/components/assignments/assigned-workload-table';
import { AssignmentOverview } from '@/components/assignments/assignment-overview';
import { DistributionPreview } from '@/components/assignments/distribution-preview';
import type { AssigneeWorkload, AssignmentSummary, DistributionPreview as DistributionPreviewData } from '@/components/assignments/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { useActiveAccount } from '@/hooks/use-active-account';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Layers3, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface GroupDefinition {
    key: string;
    label: string;
}

interface DistributionSelectOption extends SearchableSelectOption {
    id: string;
    count: number;
    balance: number | null;
}

interface SelectionSummary {
    count: number;
    balance: number | null;
}

const currency = (value: number | null) =>
    value === null ? 'Pending data' : new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);

const arraysMatch = (left: string[], right: string[]) => {
    if (left.length !== right.length) {
        return false;
    }

    const normalizedLeft = [...left].sort();
    const normalizedRight = [...right].sort();

    return normalizedLeft.every((value, index) => value === normalizedRight[index]);
};

const optionCopy = (label: string) => ({
    placeholder: `Select ${label}`,
    searchPlaceholder: `Search ${label.toLowerCase()}...`,
    emptyMessage: `No ${label.toLowerCase()} found.`,
});

const summarizeSelection = (options: DistributionSelectOption[]): SelectionSummary => {
    const totalCount = options.reduce((sum, option) => sum + option.count, 0);
    const balances = options.map((option) => option.balance).filter((balance): balance is number => balance !== null);

    return {
        count: totalCount,
        balance: balances.length > 0 ? balances.reduce((sum, balance) => sum + balance, 0) : null,
    };
};

export default function Assignments({
    summary,
    assignmentWorkloads,
    groupDefinitions,
    assignees,
}: {
    summary: AssignmentSummary;
    assignmentWorkloads: AssigneeWorkload[];
    groupDefinitions: GroupDefinition[];
    assignees: UserOption[];
}) {
    const { label: accountLabel } = useActiveAccount();
    const workspace = useClaimWorkspace();
    const isPageLoading = useInertiaLoading();
    const [activeGroupBy, setActiveGroupBy] = useState<string>('all');
    const [selectionIdsByKey, setSelectionIdsByKey] = useState<Record<string, string[]>>({});
    const [selectionMetaByKey, setSelectionMetaByKey] = useState<Record<string, DistributionSelectOption[]>>({});
    const [appliedSelectionIdsByKey, setAppliedSelectionIdsByKey] = useState<Record<string, string[]>>({});
    const [selectedAssigneeIds, setSelectedAssigneeIds] = useState<number[]>(assignees.map((user) => user.id));
    const [preview, setPreview] = useState<DistributionPreviewData | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [isDistributing, setIsDistributing] = useState(false);

    const selectedValues = useMemo(
        () => (activeGroupBy === 'all' ? [] : (appliedSelectionIdsByKey[activeGroupBy] ?? [])),
        [activeGroupBy, appliedSelectionIdsByKey],
    );
    const activeGroupLabel =
        activeGroupBy === 'all'
            ? 'All unassigned claims'
            : (groupDefinitions.find((definition) => definition.key === activeGroupBy)?.label ?? activeGroupBy);

    const selectionTotalsByKey = useMemo(
        () =>
            Object.fromEntries(
                groupDefinitions.map((definition) => [definition.key, summarizeSelection(selectionMetaByKey[definition.key] ?? [])]),
            ) as Record<string, SelectionSummary>,
        [groupDefinitions, selectionMetaByKey],
    );

    const canPreview = selectedAssigneeIds.length > 0 && (activeGroupBy === 'all' || selectedValues.length > 0);

    useEffect(() => {
        if (!canPreview) return;

        const controller = new AbortController();
        const params = new URLSearchParams({ group_by: activeGroupBy });
        selectedValues.forEach((value) => params.append('group_values[]', value));
        selectedAssigneeIds.forEach((id) => params.append('user_ids[]', String(id)));

        const loadPreview = async () => {
            await Promise.resolve();
            if (controller.signal.aborted) return;

            setPreview(null);
            setPreviewLoading(true);

            try {
                const response = await fetch(`/assignments/preview?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Unable to preview distribution.');

                setPreview((await response.json()) as DistributionPreviewData);
            } catch (error) {
                if (error instanceof Error && error.name !== 'AbortError') {
                    setPreview(null);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setPreviewLoading(false);
                }
            }
        };

        void loadPreview();

        return () => controller.abort();
    }, [activeGroupBy, canPreview, selectedAssigneeIds, selectedValues]);

    const handleDistributionSelectionChange = (key: string, values: string[], options: SearchableSelectOption[]) => {
        const normalizedOptions = options.map((option) => ({
            id: String(option.id),
            name: String(option.name),
            count: Number(option.count ?? option.claim_count ?? 0),
            balance: option.balance === null || option.balance === undefined ? null : Number(option.balance),
        }));

        setSelectionIdsByKey((current) => ({
            ...current,
            [key]: values.map(String),
        }));
        setSelectionMetaByKey((current) => ({
            ...current,
            [key]: normalizedOptions,
        }));
    };

    const isSelectionDirty = (key: string) => !arraysMatch(selectionIdsByKey[key] ?? [], appliedSelectionIdsByKey[key] ?? []);

    const applyDistributionSelection = (key: string) => {
        const nextValues = selectionIdsByKey[key] ?? [];
        setAppliedSelectionIdsByKey(nextValues.length === 0 ? {} : { [key]: nextValues });
        setActiveGroupBy(nextValues.length === 0 ? 'all' : key);
    };

    const useEntireQueue = () => {
        setActiveGroupBy('all');
        setAppliedSelectionIdsByKey({});
    };

    const toggleAssignee = (id: number) => {
        setSelectedAssigneeIds((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    };

    const distribute = () => {
        if (!preview || preview.total_claims === 0) {
            return;
        }

        router.post(
            '/assignments/distribute',
            { group_by: activeGroupBy, group_values: selectedValues, user_ids: selectedAssigneeIds },
            {
                preserveScroll: true,
                onStart: () => setIsDistributing(true),
                onSuccess: () => {
                    if (activeGroupBy !== 'all') {
                        setSelectionIdsByKey((current) => ({ ...current, [activeGroupBy]: [] }));
                        setSelectionMetaByKey((current) => ({ ...current, [activeGroupBy]: [] }));
                    }
                    setAppliedSelectionIdsByKey({});
                    setActiveGroupBy('all');
                },
                onFinish: () => setIsDistributing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Assignments', href: '/assignments' }]}>
            <Head title={`Grouped ${workspace.identifierLabel} Distribution`} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <p className="text-muted-foreground mb-1 text-xs font-semibold tracking-[0.2em] uppercase">
                        {workspace.identifierLabel}-safe routing
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">Grouped claim distribution</h1>
                    <p className="text-muted-foreground text-sm">
                        Pick a queue slice, apply it, then distribute complete {workspace.identifierLabel} groups
                        {workspace.showTrueBalance ? ' by True Balance target.' : ' evenly across assignees.'}
                    </p>
                </div>

                <AssignmentOverview formatCurrency={currency} summary={summary} />

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
                    <Card>
                        <CardHeader className="gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Layers3 className="size-5" /> Distribution selectors
                                </CardTitle>
                                <CardDescription>
                                    Each dropdown shows the matching claims{workspace.showTrueBalance ? ' and True Balance' : ''}. Apply one selector
                                    to preview and distribute that slice.
                                </CardDescription>
                            </div>
                            <Button onClick={useEntireQueue} type="button" variant={activeGroupBy === 'all' ? 'default' : 'outline'}>
                                {activeGroupBy === 'all' ? <CheckCircle2 /> : null}
                                Full queue
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="border-border bg-secondary/60 text-secondary-foreground rounded-lg border px-4 py-3 text-sm">
                                <strong>Active scope:</strong> {activeGroupLabel}
                                {activeGroupBy !== 'all' && (
                                    <span className="text-secondary-foreground/80 ml-1">
                                        with {selectedValues.length} selected {selectedValues.length === 1 ? 'value' : 'values'}.
                                    </span>
                                )}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {groupDefinitions.map((definition) => {
                                    const copy = optionCopy(definition.label);
                                    const selectionSummary = selectionTotalsByKey[definition.key] ?? { count: 0, balance: null };
                                    const isApplied = activeGroupBy === definition.key && selectedValues.length > 0;

                                    return (
                                        <div className="border-border rounded-xl border bg-white p-4" key={definition.key}>
                                            <div className="mb-3 flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-primary text-xs font-semibold tracking-wide uppercase">{definition.label}</p>
                                                    <p className="text-muted-foreground mt-1 text-xs">
                                                        {selectionSummary.count.toLocaleString()} claims
                                                        {workspace.showTrueBalance ? ` / ${currency(selectionSummary.balance)}` : ''}
                                                    </p>
                                                </div>
                                                {isApplied && (
                                                    <Badge className="border-border bg-secondary text-secondary-foreground" variant="outline">
                                                        Applied
                                                    </Badge>
                                                )}
                                            </div>

                                            <SearchableSelect
                                                multiple
                                                className="h-11"
                                                clearLabel="Clear selection"
                                                disableItemSelect
                                                emptyMessage={copy.emptyMessage}
                                                fetchUrl="/assignments/options"
                                                onSelectedValuesChange={(values, options) =>
                                                    handleDistributionSelectionChange(definition.key, values, options)
                                                }
                                                placeholder={copy.placeholder}
                                                queryParams={{ group_by: definition.key }}
                                                renderAfterClearOption={({ close }) => (
                                                    <div className="pt-1">
                                                        <Button
                                                            className="w-full"
                                                            disabled={!isSelectionDirty(definition.key)}
                                                            onClick={() => {
                                                                applyDistributionSelection(definition.key);
                                                                close();
                                                            }}
                                                            size="sm"
                                                            type="button"
                                                            variant="outline"
                                                        >
                                                            Apply selection
                                                        </Button>
                                                    </div>
                                                )}
                                                renderOption={(option, isSelected, onToggle) => {
                                                    const typedOption = option as DistributionSelectOption;

                                                    return (
                                                        <div className="flex w-full items-center justify-between gap-3">
                                                            <span className="flex min-w-0 items-center gap-2">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={isSelected}
                                                                    onChange={(event) => {
                                                                        event.stopPropagation();
                                                                        onToggle?.();
                                                                    }}
                                                                />
                                                                <span className="truncate">{typedOption.name}</span>
                                                            </span>
                                                            <span className="text-muted-foreground shrink-0 text-xs">
                                                                {typedOption.count.toLocaleString()}
                                                                {workspace.showTrueBalance ? ` / ${currency(typedOption.balance)}` : ''}
                                                            </span>
                                                        </div>
                                                    );
                                                }}
                                                searchPlaceholder={copy.searchPlaceholder}
                                                selectedValues={selectionIdsByKey[definition.key] ?? []}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="size-5" /> Assignees
                            </CardTitle>
                            <CardDescription>Each person receives complete {workspace.identifierLabel} groups.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="mb-3 flex gap-2">
                                <Button
                                    onClick={() => setSelectedAssigneeIds(assignees.map((user) => user.id))}
                                    size="sm"
                                    type="button"
                                    variant="outline"
                                >
                                    Select all
                                </Button>
                                <Button onClick={() => setSelectedAssigneeIds([])} size="sm" type="button" variant="ghost">
                                    Clear
                                </Button>
                            </div>
                            {assignees.map((user) => (
                                <label className="hover:bg-muted/50 flex cursor-pointer items-center gap-3 rounded-md px-2 py-2" key={user.id}>
                                    <Checkbox checked={selectedAssigneeIds.includes(user.id)} onCheckedChange={() => toggleAssignee(user.id)} />
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm font-medium">{user.name}</span>
                                        <span className="text-muted-foreground block truncate text-xs">{user.email}</span>
                                    </span>
                                </label>
                            ))}
                            {assignees.length === 0 && (
                                <p className="text-muted-foreground py-8 text-center text-sm">Your {accountLabel} team has no available users.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <DistributionPreview
                    activeGroupBy={activeGroupBy}
                    formatCurrency={currency}
                    isDistributing={isDistributing}
                    isLoading={canPreview && previewLoading}
                    onDistribute={distribute}
                    preview={canPreview ? preview : null}
                    selectedAssigneeCount={selectedAssigneeIds.length}
                    selectedValueCount={selectedValues.length}
                />

                <AssignedWorkloadTable formatCurrency={currency} isLoading={isPageLoading} workloads={assignmentWorkloads} />
            </div>
        </AppLayout>
    );
}
