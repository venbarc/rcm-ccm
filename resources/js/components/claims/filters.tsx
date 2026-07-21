import type { Filters, StatusOption, UserOption } from '@/components/claims/types';
import { FilterSearchSelect } from '@/components/claims/filter-search-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-react';
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
                        <div className="relative">
                            <Search className="absolute left-3 top-3 size-4 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                onChange={(event) => setLocal({ ...local, search: event.target.value })}
                                placeholder="Claim, patient, MRN, payer, provider, CPT"
                                value={local.search}
                            />
                        </div>
                        <select
                            className="h-10 rounded-md border bg-background px-3 text-sm"
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
                            className="h-10 rounded-md border bg-background px-3 text-sm"
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
                    <div className="grid gap-3 xl:grid-cols-[repeat(6,minmax(150px,1fr))_auto_auto]">
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
                        <Input onChange={(event) => setLocal({ ...local, worked_from: event.target.value })} type="date" value={local.worked_from} />
                        <Input onChange={(event) => setLocal({ ...local, worked_to: event.target.value })} type="date" value={local.worked_to} />
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
