import * as React from 'react';
import { CheckIcon, ChevronDownIcon, Loader2 } from 'lucide-react';

import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export interface SearchableSelectOption {
    id: number | string;
    name: string;
    disabled?: boolean;
    [key: string]: unknown;
}

interface SearchableSelectProps {
    value?: string;
    onValueChange?: (value: string) => void;
    multiple?: boolean;
    selectedValues?: string[];
    onSelectedValuesChange?: (values: string[], options: SearchableSelectOption[]) => void;
    closeOnSelect?: boolean;
    disableItemSelect?: boolean;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    fetchUrl: string;
    initialOption?: SearchableSelectOption | null;
    initialOptions?: SearchableSelectOption[];
    queryParams?: Record<string, string | number | null | undefined>;
    onSelectOption?: (option: SearchableSelectOption) => void;
    renderOption?: (option: SearchableSelectOption, isSelected: boolean, onToggle?: () => void) => React.ReactNode;
    renderValue?: (option: SearchableSelectOption | null) => React.ReactNode;
    renderAfterClearOption?: React.ReactNode | ((helpers: { close: () => void }) => React.ReactNode);
    clearLabel?: string;
    selectAllMatching?: boolean;
    pageSize?: number;
    className?: string;
    disabled?: boolean;
}

export function SearchableSelect({
    value,
    onValueChange,
    multiple = false,
    selectedValues = [],
    onSelectedValuesChange,
    closeOnSelect,
    disableItemSelect = false,
    placeholder = 'Select...',
    searchPlaceholder = 'Search...',
    emptyMessage = 'No results found.',
    fetchUrl,
    initialOption,
    initialOptions = [],
    queryParams,
    onSelectOption,
    renderOption,
    renderValue,
    renderAfterClearOption,
    clearLabel,
    selectAllMatching = false,
    pageSize = 10,
    className,
    disabled = false,
}: SearchableSelectProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');
    const [options, setOptions] = React.useState<SearchableSelectOption[]>([]);
    const [loading, setLoading] = React.useState(false);
    const [hasMore, setHasMore] = React.useState(true);
    const [page, setPage] = React.useState(1);
    const selectedValueSet = React.useMemo(() => new Set(selectedValues.map((val) => String(val))), [selectedValues]);
    const searchTimeoutRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);
    const queryParamsKey = JSON.stringify(queryParams ?? {});

    const availableOptions = React.useMemo(() => {
        const seededOptions = initialOption ? [initialOption, ...initialOptions] : initialOptions;
        const seen = new Set<string>();

        return [...options, ...seededOptions].filter((item) => {
            const id = String(item.id);
            if (seen.has(id)) return false;

            seen.add(id);
            return true;
        });
    }, [initialOption, initialOptions, options]);

    const selectedOption = React.useMemo(() => {
        if (multiple) return null;
        if (!value) return initialOption ?? null;

        return availableOptions.find((option) => String(option.id) === String(value)) ?? null;
    }, [availableOptions, initialOption, multiple, value]);

    const fetchData = React.useCallback(
        async (pageNum: number, searchTerm: string, append = false) => {
            setLoading(true);
            try {
                const params = new URLSearchParams({
                    page: String(pageNum),
                    per_page: String(pageSize),
                });

                const normalizedQueryParams = JSON.parse(queryParamsKey) as Record<string, string | number | null | undefined>;
                Object.entries(normalizedQueryParams).forEach(([key, val]) => {
                        if (val !== undefined && val !== null && val !== '') {
                            params.append(key, String(val));
                        }
                });

                if (searchTerm) {
                    params.append('search', searchTerm);
                }

                const response = await fetch(`${fetchUrl}?${params.toString()}`, {
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache',
                        Pragma: 'no-cache',
                    },
                });
                const data = await response.json();

                const validData = (data.data || []).filter((item: SearchableSelectOption) => item && item.id != null);
                const dedupedData = validData.filter((item: SearchableSelectOption, index: number, arr: SearchableSelectOption[]) => {
                    const id = String(item.id);

                    return arr.findIndex((candidate) => String(candidate.id) === id) === index;
                });

                if (append) {
                    setOptions((prev) => {
                        const existingIds = new Set(prev.map((option) => String(option.id)));
                        const nextItems = dedupedData.filter((item: SearchableSelectOption) => !existingIds.has(String(item.id)));

                        return [...prev, ...nextItems];
                    });
                } else {
                    setOptions(dedupedData);
                }

                setHasMore(Boolean(data.has_more));
                setPage(pageNum);
            } catch (error) {
                console.error('Failed to fetch options:', error);
            } finally {
                setLoading(false);
            }
        },
        [fetchUrl, pageSize, queryParamsKey],
    );

    React.useEffect(
        () => () => {
            if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
        },
        [],
    );

    const handleOpenChange = (nextOpen: boolean) => {
        if (nextOpen) {
            const nextSearch = multiple ? search : '';
            if (!multiple) setSearch('');
            void fetchData(1, nextSearch);
        }

        setOpen(nextOpen);
    };

    const handleSearch = React.useCallback(
        (searchTerm: string) => {
            setSearch(searchTerm);
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }

            searchTimeoutRef.current = setTimeout(() => {
                void fetchData(1, searchTerm);
            }, 300);
        },
        [fetchData],
    );

    const handleScroll = React.useCallback(
        (event: React.UIEvent<HTMLDivElement>) => {
            const target = event.target as HTMLDivElement;
            const nearBottom = target.scrollHeight - target.scrollTop <= target.clientHeight + 50;

            if (!nearBottom || !hasMore || loading) {
                return;
            }

            const nextPage = page + 1;
            void fetchData(nextPage, search, true);
        },
        [hasMore, loading, page, search, fetchData],
    );

    const handleSelect = React.useCallback(
        (option: SearchableSelectOption) => {
            if (option.disabled) {
                return;
            }

            if (multiple) {
                const currentSearch = search;
                const optionId = String(option.id);
                const nextValues = selectedValueSet.has(optionId)
                    ? selectedValues.filter((selectedValue) => String(selectedValue) !== optionId)
                    : [...selectedValues, optionId];
                const nextOptions = availableOptions.filter((item) => nextValues.includes(String(item.id)));

                onSelectedValuesChange?.(nextValues, nextOptions);
                onSelectOption?.(option);

                if (currentSearch) {
                    window.setTimeout(() => {
                        setSearch(currentSearch);
                    }, 0);
                }

                if (closeOnSelect) {
                    setOpen(false);
                }

                return;
            }

            onSelectOption?.(option);
            onValueChange?.(String(option.id));
            setOpen(false);
        },
        [availableOptions, closeOnSelect, multiple, onSelectOption, onSelectedValuesChange, onValueChange, search, selectedValueSet, selectedValues],
    );

    const handleClear = React.useCallback(() => {
        if (multiple) {
            onSelectedValuesChange?.([], []);
        } else {
            onValueChange?.('');
        }

        setOpen(false);
        setSearch('');
    }, [multiple, onSelectedValuesChange, onValueChange]);

    const handleSelectAllMatching = React.useCallback(() => {
        const matchingOptions = options.filter((option) => !option.disabled);
        const nextValues = Array.from(new Set([...selectedValues, ...matchingOptions.map((option) => String(option.id))]));
        const optionById = new Map(availableOptions.map((option) => [String(option.id), option]));
        const nextOptions = nextValues.map((selectedValue) => optionById.get(selectedValue)).filter(Boolean) as SearchableSelectOption[];

        onSelectedValuesChange?.(nextValues, nextOptions);
    }, [availableOptions, onSelectedValuesChange, options, selectedValues]);
    const allMatchingSelected =
        options.length > 0 && options.filter((option) => !option.disabled).every((option) => selectedValueSet.has(String(option.id)));

    const displayValue = multiple
        ? selectedValues.length > 1
            ? `${selectedValues.length} selected`
            : selectedValues.length === 1
              ? availableOptions.find((option) => String(option.id) === String(selectedValues[0]))?.name || String(selectedValues[0])
              : placeholder
        : renderValue
          ? renderValue(selectedOption)
          : selectedOption?.name || placeholder;

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild disabled={disabled}>
                <button
                    type="button"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'border-input data-[placeholder]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex h-10 w-full items-center justify-between gap-2 overflow-hidden rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                        'min-w-0 max-w-full text-left',
                        !selectedOption && !multiple && 'text-muted-foreground',
                        className,
                    )}
                    data-placeholder={!selectedOption && !multiple ? '' : undefined}
                >
                    <div className={cn('min-w-0 max-w-full flex-1 truncate overflow-hidden', renderValue && '[&>*]:block [&>*]:w-full [&>*]:max-w-full [&>*]:min-w-0')}>
                        {displayValue}
                    </div>
                    <ChevronDownIcon className="size-4 shrink-0 opacity-50" />
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput placeholder={searchPlaceholder} value={search} onValueChange={handleSearch} />
                    <CommandList onScroll={handleScroll} onWheel={(event) => event.stopPropagation()} className="overscroll-contain">
                        <CommandEmpty>{loading ? 'Loading...' : emptyMessage}</CommandEmpty>
                        <CommandGroup>
                            {clearLabel && (
                                <CommandItem
                                    value="__clear__"
                                    onSelect={handleClear}
                                    disabled={multiple ? selectedValues.length === 0 : !value && !selectedOption}
                                    className="text-muted-foreground"
                                >
                                    <span className="flex-1 truncate">{clearLabel}</span>
                                </CommandItem>
                            )}
                            {multiple && selectAllMatching && search.trim() !== '' && options.length > 0 && (
                                <CommandItem
                                    value="__select_all_matching__"
                                    onSelect={handleSelectAllMatching}
                                    disabled={loading || allMatchingSelected}
                                >
                                    <CheckIcon className="mr-2 size-4 shrink-0" />
                                    <span className="flex-1 truncate">
                                        {allMatchingSelected
                                            ? `All matching “${search.trim()}” payers selected`
                                            : `Select all matching “${search.trim()}” payers`}
                                    </span>
                                </CommandItem>
                            )}
                            {renderAfterClearOption && (
                                <div className="px-2 py-1" onMouseDown={(event) => event.preventDefault()}>
                                    {typeof renderAfterClearOption === 'function'
                                        ? renderAfterClearOption({ close: () => setOpen(false) })
                                        : renderAfterClearOption}
                                </div>
                            )}
                            {availableOptions.map((option) => {
                                const isSelected = multiple ? selectedValueSet.has(String(option.id)) : value === String(option.id);
                                const toggleOption = () => handleSelect(option);

                                return (
                                    <CommandItem
                                        key={option.id}
                                        value={String(option.id)}
                                        onSelect={disableItemSelect ? undefined : () => handleSelect(option)}
                                        disabled={Boolean(option.disabled)}
                                        className={cn(option.disabled && 'cursor-not-allowed opacity-50')}
                                    >
                                        <span className="flex-1 truncate">
                                            {renderOption ? renderOption(option, isSelected, toggleOption) : option.name}
                                        </span>
                                        {isSelected && (!multiple || !renderOption) && <CheckIcon className="size-4 shrink-0" />}
                                    </CommandItem>
                                );
                            })}
                            {loading && (
                                <div className="flex items-center justify-center py-2">
                                    <Loader2 className="size-4 animate-spin" />
                                </div>
                            )}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
