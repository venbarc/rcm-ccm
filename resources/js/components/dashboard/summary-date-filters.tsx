import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { X } from 'lucide-react';
import type { DashboardPanelDateFilters } from './types';

type FilterPrefix = 'claims_status' | 'cpt' | 'modmed' | 'invoiced' | 'credit_status';

interface SummaryDateFiltersProps {
    filters: DashboardPanelDateFilters;
    prefix: FilterPrefix;
    showServiceDate?: boolean;
    invoiceDateLabel?: string;
    invoiceDatePlaceholder?: string;
}

function currentDashboardParams(): Record<string, string> {
    if (typeof window === 'undefined') {
        return {};
    }

    return Object.fromEntries(new URLSearchParams(window.location.search).entries());
}

export function SummaryDateFilters({
    filters,
    prefix,
    showServiceDate = true,
    invoiceDateLabel = 'CF Invoice Date Range',
    invoiceDatePlaceholder = 'Select CF invoice date range',
}: SummaryDateFiltersProps) {
    const visitDashboard = (params: Record<string, string>) => {
        router.get('/dashboard', params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const updateRange = (kind: 'invoice' | 'service', from: string, to: string) => {
        const params = currentDashboardParams();
        const startKey = `${prefix}_${kind}_start`;
        const endKey = `${prefix}_${kind}_end`;

        if (from) {
            params[startKey] = from;
        } else {
            delete params[startKey];
        }

        if (to) {
            params[endKey] = to;
        } else {
            delete params[endKey];
        }

        visitDashboard(params);
    };

    const clearFilters = () => {
        const params = currentDashboardParams();
        const filterKinds: Array<'invoice' | 'service'> = showServiceDate ? ['invoice', 'service'] : ['invoice'];

        filterKinds.forEach((kind) => {
            delete params[`${prefix}_${kind}_start`];
            delete params[`${prefix}_${kind}_end`];
        });

        visitDashboard(params);
    };

    const hasActiveFilters =
        Boolean(filters.invoiceStart || filters.invoiceEnd) || (showServiceDate && Boolean(filters.serviceStart || filters.serviceEnd));

    return (
        <div className="flex flex-wrap items-end gap-3">
            <DateRangeFilterField
                className="w-full sm:w-72"
                from={filters.invoiceStart ?? ''}
                label={invoiceDateLabel}
                onApply={({ from, to }) => updateRange('invoice', from, to)}
                placeholder={invoiceDatePlaceholder}
                to={filters.invoiceEnd ?? ''}
            />
            {showServiceDate && (
                <DateRangeFilterField
                    className="w-full sm:w-72"
                    from={filters.serviceStart ?? ''}
                    label="Service Date Range"
                    onApply={({ from, to }) => updateRange('service', from, to)}
                    placeholder="Select service date range"
                    to={filters.serviceEnd ?? ''}
                />
            )}
            <Button className="shrink-0" type="button" variant="ghost" size="sm" disabled={!hasActiveFilters} onClick={clearFilters}>
                <X className="size-4" />
                Clear filters
            </Button>
        </div>
    );
}
