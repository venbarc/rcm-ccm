import { formatServiceMonth } from '@/components/claims/utils';
import { SearchableSelect } from '@/components/ui/searchable-select';

interface FilterSearchSelectProps {
    filter: 'claim_status' | 'payer_name' | 'rendering_provider' | 'denial_reason' | 'procedure_code' | 'service_month';
    value: string;
    onValueChange: (value: string) => void;
    placeholder: string;
    searchPlaceholder: string;
    emptyMessage: string;
}

export function FilterSearchSelect({ filter, value, onValueChange, placeholder, searchPlaceholder, emptyMessage }: FilterSearchSelectProps) {
    const selectedLabel = filter === 'service_month' && value ? formatServiceMonth(value) : value;

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
