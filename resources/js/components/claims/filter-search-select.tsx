import { formatServiceMonth, longDate } from '@/components/claims/utils';
import { SearchableSelect } from '@/components/ui/searchable-select';

interface FilterSearchSelectProps {
    filter: 'modmed_claim_status' | 'payer_name' | 'primary_provider' | 'denial_reason' | 'procedure_code' | 'service_month' | 'invoiced_status_date';
    value: string;
    onValueChange: (value: string) => void;
    placeholder: string;
    searchPlaceholder: string;
    emptyMessage: string;
}

export function FilterSearchSelect({ filter, value, onValueChange, placeholder, searchPlaceholder, emptyMessage }: FilterSearchSelectProps) {
    const selectedLabel =
        filter === 'service_month' && value ? formatServiceMonth(value) : filter === 'invoiced_status_date' && value ? longDate(value) : value;

    return (
        <SearchableSelect
            value={value}
            onValueChange={onValueChange}
            placeholder={placeholder}
            searchPlaceholder={searchPlaceholder}
            emptyMessage={emptyMessage}
            fetchUrl="/claims/options"
            queryParams={{ filter }}
            initialOption={value ? { id: value, name: selectedLabel } : null}
            clearLabel={`Clear ${placeholder.toLowerCase()}`}
        />
    );
}
