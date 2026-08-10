import { formatServiceMonth } from '@/components/claims/utils';
import { SearchableSelect } from '@/components/ui/searchable-select';

interface FilterSearchSelectProps {
    filter: 'modmed_claim_status' | 'payer_name' | 'primary_provider' | 'procedure_code' | 'service_month' | 'location';
    value: string;
    onValueChange: (value: string) => void;
    placeholder: string;
    searchPlaceholder: string;
    emptyMessage: string;
    selectedLabel?: string;
    multiple?: boolean;
    selectAllMatching?: boolean;
}

export function parseMultiFilterValue(value: string): string[] {
    if (!value) {
        return [];
    }

    try {
        const decoded = JSON.parse(value);

        if (Array.isArray(decoded)) {
            return decoded.filter((item): item is string => typeof item === 'string' && item.trim() !== '');
        }
    } catch {
        // Preserve compatibility with existing single-value filter URLs.
    }

    return [value];
}

export function FilterSearchSelect({
    filter,
    value,
    onValueChange,
    placeholder,
    searchPlaceholder,
    emptyMessage,
    selectedLabel,
    multiple = false,
    selectAllMatching = false,
}: FilterSearchSelectProps) {
    const resolvedSelectedLabel = selectedLabel ?? (filter === 'service_month' && value ? formatServiceMonth(value) : value);
    const selectedValues = multiple ? parseMultiFilterValue(value) : [];

    return (
        <SearchableSelect
            value={value}
            onValueChange={onValueChange}
            multiple={multiple}
            selectedValues={selectedValues}
            onSelectedValuesChange={(values) => onValueChange(values.length > 0 ? JSON.stringify(values) : '')}
            placeholder={placeholder}
            searchPlaceholder={searchPlaceholder}
            emptyMessage={emptyMessage}
            fetchUrl="/claims/options"
            queryParams={{ filter }}
            initialOption={!multiple && value ? { id: value, name: resolvedSelectedLabel } : null}
            initialOptions={selectedValues.map((selectedValue) => ({ id: selectedValue, name: selectedValue }))}
            clearLabel={`Clear ${placeholder.toLowerCase()}`}
            closeOnSelect={!multiple}
            selectAllMatching={selectAllMatching}
            pageSize={multiple ? 200 : 10}
        />
    );
}
