import { FilterSearchSelect } from '@/components/claims/filter-search-select';
import type { Filters, StatusOption, UserOption } from '@/components/claims/types';
import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface ClaimsFiltersProps {
    local: Filters;
    workStatuses: StatusOption[];
    modMedClaimStatuses: StatusOption[];
    invoicedStatuses: StatusOption[];
    creditStatuses: StatusOption[];
    creditReasons: StatusOption[];
    denialReasons: StatusOption[];
    assignees: UserOption[];
    onFilterChange: (values: Record<string, string>) => void;
    onSearchChange: (value: string) => void;
    onClear: () => void;
}

export function ClaimsFilters({
    local,
    workStatuses,
    modMedClaimStatuses,
    invoicedStatuses,
    creditStatuses,
    creditReasons,
    denialReasons,
    assignees,
    onFilterChange,
    onSearchChange,
    onClear,
}: ClaimsFiltersProps) {
    return (
        <Card className="w-full max-w-full min-w-0">
            <CardContent className="min-w-0 p-4">
                <div className="min-w-0 space-y-4">
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div className="md:col-span-2">
                            <SearchInput
                                aria-label="Search claims"
                                className="w-full"
                                onChange={(event) => onSearchChange(event.target.value)}
                                placeholder="Bill ID, patient, MRN, payer, provider, CPT"
                                type="search"
                                value={local.search}
                            />
                        </div>
                        <select
                            className="bg-background h-10 min-w-0 rounded-md border px-3 text-sm"
                            onChange={(event) => onFilterChange({ work_status: event.target.value })}
                            value={local.work_status}
                        >
                            <option value="">All work statuses</option>
                            {workStatuses.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                        <select
                            className="bg-background h-10 min-w-0 rounded-md border px-3 text-sm"
                            onChange={(event) => onFilterChange({ assigned_to: event.target.value })}
                            value={local.assigned_to}
                        >
                            <option value="">All assignees</option>
                            <option value="unassigned">Unassigned</option>
                            <option value="me">Assigned to me</option>
                            {assignees.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <FilterSearchSelect
                            filter="payer_name"
                            value={local.payer_name}
                            onValueChange={(value) => onFilterChange({ payer_name: value })}
                            multiple
                            selectAllMatching
                            placeholder="All payers"
                            searchPlaceholder="Search payers..."
                            emptyMessage="No payers found."
                        />
                        <FilterSearchSelect
                            filter="primary_provider"
                            value={local.primary_provider}
                            onValueChange={(value) => onFilterChange({ primary_provider: value })}
                            placeholder="All primary providers"
                            searchPlaceholder="Search primary providers..."
                            emptyMessage="No primary providers found."
                        />
                        <FilterSearchSelect
                            filter="modmed_claim_status"
                            value={local.modmed_claim_status}
                            onValueChange={(value) => onFilterChange({ modmed_claim_status: value })}
                            placeholder="All ModMed statuses"
                            searchPlaceholder="Search ModMed statuses..."
                            emptyMessage="No ModMed statuses found."
                            selectedLabel={modMedClaimStatuses.find((item) => item.value === local.modmed_claim_status)?.label}
                        />
                        <FilterSearchSelect
                            filter="procedure_code"
                            value={local.procedure_code}
                            onValueChange={(value) => onFilterChange({ procedure_code: value })}
                            placeholder="All CPT codes"
                            searchPlaceholder="Search CPT codes..."
                            emptyMessage="No CPT codes found."
                        />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                        <select
                            className="bg-background h-10 min-w-0 rounded-md border px-3 text-sm"
                            onChange={(event) => onFilterChange({ invoiced_status: event.target.value })}
                            value={local.invoiced_status}
                        >
                            <option value="">All invoiced statuses</option>
                            {invoicedStatuses.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                        <select
                            className="bg-background h-10 min-w-0 rounded-md border px-3 text-sm"
                            onChange={(event) => onFilterChange({ credit_status: event.target.value })}
                            value={local.credit_status}
                        >
                            <option value="">All credit statuses</option>
                            {creditStatuses.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                        <select
                            className="bg-background h-10 min-w-0 rounded-md border px-3 text-sm"
                            onChange={(event) => onFilterChange({ credit_reason: event.target.value })}
                            value={local.credit_reason}
                        >
                            <option value="">All credit reasons</option>
                            {creditReasons.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                        <select
                            className="bg-background h-10 min-w-0 rounded-md border px-3 text-sm"
                            onChange={(event) => onFilterChange({ denial_reason: event.target.value })}
                            value={local.denial_reason}
                        >
                            <option value="">All denial reasons</option>
                            {denialReasons.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                        <FilterSearchSelect
                            filter="service_month"
                            value={local.service_month}
                            onValueChange={(value) => onFilterChange({ service_month: value })}
                            placeholder="All service months"
                            searchPlaceholder="Search service months..."
                            emptyMessage="No service months found."
                        />
                    </div>
                    <div className="grid items-end gap-3 md:grid-cols-2 2xl:grid-cols-[repeat(3,minmax(240px,1fr))_auto]">
                        <DateRangeFilterField
                            from={local.cf_invoice_from}
                            label="CF Invoice Date Range"
                            onApply={({ from, to }) => onFilterChange({ cf_invoice_from: from, cf_invoice_to: to })}
                            placeholder="CF invoice date range"
                            to={local.cf_invoice_to}
                        />
                        <DateRangeFilterField
                            from={local.credit_status_from}
                            label="Credit Status Date Range"
                            onApply={({ from, to }) => onFilterChange({ credit_status_from: from, credit_status_to: to })}
                            placeholder="Credit status date range"
                            to={local.credit_status_to}
                        />
                        <DateRangeFilterField
                            from={local.worked_from}
                            label="Worked Date Range"
                            onApply={({ from, to }) => onFilterChange({ worked_from: from, worked_to: to })}
                            placeholder="Worked date range"
                            to={local.worked_to}
                        />
                        <Button className="md:justify-self-end" onClick={onClear} type="button" variant="ghost">
                            Clear
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
