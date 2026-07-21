import { FilterSearchSelect } from '@/components/claims/filter-search-select';
import type { Filters, StatusOption, UserOption } from '@/components/claims/types';
import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { type FormEvent } from 'react';

interface ClaimsFiltersProps {
    local: Filters;
    setLocal: (filters: Filters) => void;
    workStatuses: StatusOption[];
    assignees: UserOption[];
    onSubmit: (event: FormEvent) => void;
    onClear: () => void;
}

export function ClaimsFilters({ local, setLocal, workStatuses, assignees, onSubmit, onClear }: ClaimsFiltersProps) {
    return (
        <Card>
            <CardContent className="p-4">
                <form className="space-y-3" onSubmit={onSubmit}>
                    <div className="grid gap-3 xl:grid-cols-[minmax(240px,1.5fr)_repeat(4,minmax(170px,1fr))]">
                        <SearchInput
                            onChange={(event) => setLocal({ ...local, search: event.target.value })}
                            placeholder="Claim, patient, MRN, payer, provider, CPT"
                            value={local.search}
                        />
                        <select
                            className="bg-background h-10 rounded-md border px-3 text-sm"
                            onChange={(event) => setLocal({ ...local, work_status: event.target.value })}
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
                            onValueChange={(value) => setLocal({ ...local, payer_name: value })}
                            placeholder="All payers"
                            searchPlaceholder="Search payers..."
                            emptyMessage="No payers found."
                        />
                        <FilterSearchSelect
                            filter="rendering_provider"
                            value={local.rendering_provider}
                            onValueChange={(value) => setLocal({ ...local, rendering_provider: value })}
                            placeholder="All providers"
                            searchPlaceholder="Search providers..."
                            emptyMessage="No providers found."
                        />
                        <select
                            className="bg-background h-10 rounded-md border px-3 text-sm"
                            onChange={(event) => setLocal({ ...local, assigned_to: event.target.value })}
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
                    <div className="grid gap-3 xl:grid-cols-[repeat(4,minmax(150px,1fr))_minmax(280px,2fr)_auto_auto]">
                        <FilterSearchSelect
                            filter="claim_status"
                            value={local.claim_status}
                            onValueChange={(value) => setLocal({ ...local, claim_status: value })}
                            placeholder="All claim statuses"
                            searchPlaceholder="Search claim statuses..."
                            emptyMessage="No claim statuses found."
                        />
                        <FilterSearchSelect
                            filter="procedure_code"
                            value={local.procedure_code}
                            onValueChange={(value) => setLocal({ ...local, procedure_code: value })}
                            placeholder="All CPT codes"
                            searchPlaceholder="Search CPT codes..."
                            emptyMessage="No CPT codes found."
                        />
                        <FilterSearchSelect
                            filter="denial_reason"
                            value={local.denial_reason}
                            onValueChange={(value) => setLocal({ ...local, denial_reason: value })}
                            placeholder="All denial reasons"
                            searchPlaceholder="Search denial reasons..."
                            emptyMessage="No denial reasons found."
                        />
                        <FilterSearchSelect
                            filter="service_month"
                            value={local.service_month}
                            onValueChange={(value) => setLocal({ ...local, service_month: value })}
                            placeholder="All service months"
                            searchPlaceholder="Search service months..."
                            emptyMessage="No service months found."
                        />
                        <DateRangeFilterField
                            from={local.worked_from}
                            hideLabel
                            label="Worked Date Range"
                            onApply={({ from, to }) => setLocal({ ...local, worked_from: from, worked_to: to })}
                            placeholder="Worked date range"
                            to={local.worked_to}
                        />
                        <Button type="submit">Apply</Button>
                        <Button onClick={onClear} type="button" variant="ghost">
                            Clear
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
