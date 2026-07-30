import { FilterSearchSelect } from '@/components/claims/filter-search-select';
import type { Filters, StatusOption, UserOption } from '@/components/claims/types';
import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface ClaimsFiltersProps {
    local: Filters;
    workStatuses: StatusOption[];
    assignees: UserOption[];
    onFilterChange: (values: Record<string, string>) => void;
    onSearchChange: (value: string) => void;
    onClear: () => void;
}

export function ClaimsFilters({ local, workStatuses, assignees, onFilterChange, onSearchChange, onClear }: ClaimsFiltersProps) {
    return (
        <Card className="w-full max-w-full min-w-0">
            <CardContent className="min-w-0 p-4">
                <div className="min-w-0 space-y-3">
                    <div className="grid gap-3 xl:grid-cols-[minmax(240px,1.5fr)_repeat(4,minmax(170px,1fr))]">
                        <SearchInput
                            aria-label="Search claims"
                            onChange={(event) => onSearchChange(event.target.value)}
                            placeholder="Bill ID, patient, MRN, payer, provider, CPT"
                            type="search"
                            value={local.search}
                        />
                        <select
                            className="bg-background h-10 rounded-md border px-3 text-sm"
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
                        <FilterSearchSelect
                            filter="payer_name"
                            value={local.payer_name}
                            onValueChange={(value) => onFilterChange({ payer_name: value })}
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
                        <select
                            className="bg-background h-10 rounded-md border px-3 text-sm"
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
                    <div className="grid gap-3 xl:grid-cols-[repeat(4,minmax(150px,1fr))_minmax(250px,1.5fr)_minmax(250px,1.5fr)_auto]">
                        <FilterSearchSelect
                            filter="modmed_claim_status"
                            value={local.modmed_claim_status}
                            onValueChange={(value) => onFilterChange({ modmed_claim_status: value })}
                            placeholder="All ModMed statuses"
                            searchPlaceholder="Search ModMed statuses..."
                            emptyMessage="No ModMed statuses found."
                        />
                        <FilterSearchSelect
                            filter="procedure_code"
                            value={local.procedure_code}
                            onValueChange={(value) => onFilterChange({ procedure_code: value })}
                            placeholder="All CPT codes"
                            searchPlaceholder="Search CPT codes..."
                            emptyMessage="No CPT codes found."
                        />
                        <FilterSearchSelect
                            filter="denial_reason"
                            value={local.denial_reason}
                            onValueChange={(value) => onFilterChange({ denial_reason: value })}
                            placeholder="All denial reasons"
                            searchPlaceholder="Search denial reasons..."
                            emptyMessage="No denial reasons found."
                        />
                        <FilterSearchSelect
                            filter="service_month"
                            value={local.service_month}
                            onValueChange={(value) => onFilterChange({ service_month: value })}
                            placeholder="All service months"
                            searchPlaceholder="Search service months..."
                            emptyMessage="No service months found."
                        />
                        <DateRangeFilterField
                            from={local.cf_invoice_from}
                            hideLabel
                            label="CF Invoice Date Range"
                            onApply={({ from, to }) => onFilterChange({ cf_invoice_from: from, cf_invoice_to: to })}
                            placeholder="CF invoice date range"
                            to={local.cf_invoice_to}
                        />
                        <DateRangeFilterField
                            from={local.worked_from}
                            hideLabel
                            label="Worked Date Range"
                            onApply={({ from, to }) => onFilterChange({ worked_from: from, worked_to: to })}
                            placeholder="Worked date range"
                            to={local.worked_to}
                        />
                        <Button onClick={onClear} type="button" variant="ghost">
                            Clear
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
