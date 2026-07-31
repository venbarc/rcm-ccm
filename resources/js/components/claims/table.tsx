import { ClaimSourceLineCells, ClaimSourceLineHeaders } from '@/components/claims/claim-source-line-columns';
import type { ClaimGroup, Filters, SortColumn } from '@/components/claims/types';
import {
    EMPTY_VALUE,
    currency,
    lineProcedureCode,
    serviceDateRange,
    statusLabel,
    workStatusBackgroundStyle,
    workStatusBadgeStyle,
} from '@/components/claims/utils';
import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp, CalendarDays, ChevronDown, Eye, Pencil } from 'lucide-react';
import { Fragment } from 'react';

function SortHeader({
    column,
    label,
    filters,
    onSort,
    right = false,
}: {
    column: SortColumn;
    label: string;
    filters: Filters;
    onSort: (column: SortColumn) => void;
    right?: boolean;
}) {
    const active = filters.sort_by === column;
    const Icon = filters.sort_direction === 'asc' ? ArrowUp : ArrowDown;

    return (
        <th className={cn('px-4 py-3 font-medium', right ? 'text-right' : 'text-left')}>
            <button className="hover:text-foreground inline-flex items-center gap-1" onClick={() => onSort(column)} type="button">
                {label}
                {active && <Icon className="size-3" />}
            </button>
        </th>
    );
}

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

interface ClaimsTableProps {
    claims: {
        data: ClaimGroup[];
        links: Parameters<typeof Pagination>[0]['links'];
        from: number | null;
        to: number | null;
        total: number;
        current_page: number;
    };
    filters: Filters;
    expandedClaimId: number | null;
    onToggleClaim: (id: number) => void;
    onSort: (column: SortColumn) => void;
    onEditLine: (claim: ClaimGroup, line: ClaimGroup['lines'][number]) => void;
}

export function ClaimsTable({ claims, filters, expandedClaimId, onToggleClaim, onSort, onEditLine }: ClaimsTableProps) {
    const isLoading = useInertiaLoading();
    const claimViewUrl = (claim: ClaimGroup) => {
        const params = new URLSearchParams();

        Object.entries(filters).forEach(([key, value]) => {
            if (value && key !== 'expanded') {
                params.set(key, value);
            }
        });
        params.set('expanded', String(claim.id));
        params.set('page', String(claims.current_page));

        return `/claims/${claim.id}?return_to=${encodeURIComponent(`/claims?${params.toString()}`)}`;
    };

    return (
        <Card className="w-full max-w-full min-w-0 overflow-hidden rounded-xl">
            <DataLoadingOverlay className="max-w-full min-w-0" isLoading={isLoading} label="Loading claims...">
                <div className="border-b p-4">
                    <Pagination links={claims.links} />
                </div>
                <div className="w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain">
                    <table className="w-full min-w-[1540px] text-sm">
                        <thead className="bg-muted/30 text-muted-foreground border-b">
                            <tr>
                                <th className="w-12 px-2 py-3 text-left font-medium">#</th>
                                <SortHeader column="bill_id" label="Bill ID" filters={filters} onSort={onSort} />
                                <SortHeader column="patient_name" label="Patient" filters={filters} onSort={onSort} />
                                <SortHeader column="payer_name" label="Payer" filters={filters} onSort={onSort} />
                                <SortHeader column="primary_provider" label="Primary Provider" filters={filters} onSort={onSort} />
                                <SortHeader column="location" label="Location" filters={filters} onSort={onSort} />
                                <th className="px-4 py-3 text-center font-medium">Modified By</th>
                                <SortHeader column="service_date_start" label="Service Date" filters={filters} onSort={onSort} />
                                <SortHeader column="line_count" label="CPT Lines" filters={filters} onSort={onSort} right />
                                <SortHeader column="true_charge" label="True Charge" filters={filters} onSort={onSort} right />
                                <SortHeader column="payments" label="Payments" filters={filters} onSort={onSort} right />
                                <SortHeader column="true_balance" label="True Balance" filters={filters} onSort={onSort} right />
                                <th className="bg-muted sticky right-0 z-20 w-20 min-w-20 border-l px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {claims.data.map((claim, index) => {
                                const isExpanded = expandedClaimId === claim.id;

                                return (
                                    <Fragment key={claim.id}>
                                        <tr className="group hover:bg-muted/50">
                                            <td className="text-muted-foreground px-2 py-3">{(claims.from ?? 1) + index}</td>
                                            <td className="px-4 py-3">
                                                <button
                                                    className="font-medium text-blue-600 hover:underline"
                                                    onClick={() => onToggleClaim(claim.id)}
                                                    type="button"
                                                >
                                                    {claim.bill_id}
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 font-medium">{claim.patient_name}</td>
                                            <td className="max-w-56 px-4 py-3">
                                                <p className="truncate" title={claim.payer_name || undefined}>
                                                    {claim.payer_name || EMPTY_VALUE}
                                                </p>
                                            </td>
                                            <td className="max-w-56 px-4 py-3">
                                                <p className="truncate" title={claim.primary_provider || undefined}>
                                                    {claim.primary_provider || EMPTY_VALUE}
                                                </p>
                                            </td>
                                            <td className="max-w-[420px] px-4 py-3">
                                                <p className="truncate">{claim.facility || claim.location || EMPTY_VALUE}</p>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {claim.modified_by ? (
                                                    <span
                                                        className="inline-flex size-7 items-center justify-center rounded-full border border-indigo-300 bg-white text-[10px] font-semibold text-indigo-900"
                                                        title={claim.modified_by.name}
                                                    >
                                                        {initials(claim.modified_by.name)}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">{EMPTY_VALUE}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center gap-1.5 whitespace-nowrap">
                                                    <CalendarDays className="text-muted-foreground size-3.5" />
                                                    {serviceDateRange(claim.service_date_start, claim.service_date_end)}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <button aria-expanded={isExpanded} onClick={() => onToggleClaim(claim.id)} type="button">
                                                    <Badge
                                                        className="cursor-pointer gap-1 border-0 bg-indigo-50 text-indigo-800 hover:bg-indigo-100"
                                                        variant="secondary"
                                                    >
                                                        {claim.line_count}
                                                        <ChevronDown className={cn('size-3 transition-transform', isExpanded && 'rotate-180')} />
                                                    </Badge>
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium tabular-nums">{currency(claim.true_charge)}</td>
                                            <td className="px-4 py-3 text-right font-medium text-green-600 tabular-nums">
                                                {currency(claim.payments)}
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium text-orange-600 tabular-nums">
                                                {currency(claim.true_balance)}
                                            </td>
                                            <td className="bg-card group-hover:bg-muted sticky right-0 z-10 w-20 min-w-20 border-l px-4 py-3 text-right">
                                                <Button asChild size="icon" title={`View Bill ID ${claim.bill_id}`} variant="ghost">
                                                    <Link aria-label={`View Bill ID ${claim.bill_id}`} href={claimViewUrl(claim)}>
                                                        <Eye className="size-4" />
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                        {isExpanded && (
                                            <tr className="bg-muted/40">
                                                <td className="max-w-0 p-0" colSpan={13}>
                                                    <div className="w-full max-w-full min-w-0 px-8 py-3">
                                                        <div className="w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain [contain:inline-size]">
                                                            <table className="w-full min-w-[2070px] text-sm">
                                                                <thead className="text-muted-foreground">
                                                                    <tr className="border-b">
                                                                        <ClaimSourceLineHeaders showPayer={false} showPrimaryProvider={false} />
                                                                        <th className="px-3 py-3 text-left font-medium">Denial Reason</th>
                                                                        <th className="px-3 py-3 text-left font-medium">Work Status</th>
                                                                        <th className="px-3 py-3 text-left font-medium">Assigned To</th>
                                                                        <th className="bg-muted sticky right-0 z-20 w-20 min-w-20 border-l px-3 py-3 text-right font-medium">
                                                                            Actions
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    {claim.lines.map((line) => (
                                                                        <tr
                                                                            className="group border-muted-foreground/20 border-b last:border-0"
                                                                            key={line.id}
                                                                            style={workStatusBackgroundStyle(line.work_status_color)}
                                                                        >
                                                                            <ClaimSourceLineCells
                                                                                line={{
                                                                                    ...line,
                                                                                    cpt_code: lineProcedureCode(line.procedure_code, line.cpt_code),
                                                                                }}
                                                                                showPayer={false}
                                                                                showPrimaryProvider={false}
                                                                            />
                                                                            <td className="text-muted-foreground px-3 py-3">
                                                                                {line.denial_reason_label || line.denial_reason || EMPTY_VALUE}
                                                                            </td>
                                                                            <td className="px-3 py-3">
                                                                                <Badge
                                                                                    className="font-medium"
                                                                                    style={workStatusBadgeStyle(line.work_status_color)}
                                                                                    variant="outline"
                                                                                >
                                                                                    {line.work_status_label || statusLabel(line.work_status)}
                                                                                </Badge>
                                                                            </td>
                                                                            <td className="px-3 py-3">
                                                                                <div className="bg-background min-w-44 rounded-md border px-3 py-2">
                                                                                    {line.assignee?.name ?? 'Unassigned'}
                                                                                </div>
                                                                            </td>
                                                                            <td className="sticky right-0 z-10 w-20 min-w-20 border-l bg-inherit px-3 py-3 text-right">
                                                                                <Button
                                                                                    aria-label={`Edit CPT ${lineProcedureCode(line.procedure_code, line.cpt_code)}`}
                                                                                    onClick={() => onEditLine(claim, line)}
                                                                                    size="icon"
                                                                                    title="Edit CPT line"
                                                                                    variant="ghost"
                                                                                >
                                                                                    <Pencil className="size-4" />
                                                                                </Button>
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </Fragment>
                                );
                            })}
                            {claims.data.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground p-14 text-center" colSpan={13}>
                                        No claims match these filters.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="border-t p-4">
                    <Pagination links={claims.links} />
                </div>
            </DataLoadingOverlay>
        </Card>
    );
}
