import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

interface DateRangePickerProps {
    from?: string;
    to?: string;
    onApply: (range: { from?: string; to?: string }) => void;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
}

const weekdayLabels = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
const monthFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    year: 'numeric',
});
const longDateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

function parseIsoDate(value?: string): Date | null {
    if (!value) return null;

    const parts = value.split('-').map(Number);
    if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) return null;

    const [year, month, day] = parts;
    const parsed = new Date(year, month - 1, day);

    if (parsed.getFullYear() !== year || parsed.getMonth() !== month - 1 || parsed.getDate() !== day) return null;

    return parsed;
}

function formatIsoDate(value: Date): string {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const startOfMonth = (value: Date) => new Date(value.getFullYear(), value.getMonth(), 1);
const addMonths = (value: Date, amount: number) => new Date(value.getFullYear(), value.getMonth() + amount, 1);

function buildMonthGrid(month: Date): Array<Date | null> {
    const year = month.getFullYear();
    const monthIndex = month.getMonth();
    const firstDayOfMonth = new Date(year, monthIndex, 1);
    const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
    const mondayFirstOffset = (firstDayOfMonth.getDay() + 6) % 7;
    const cells: Array<Date | null> = [];

    for (let index = 0; index < mondayFirstOffset; index++) cells.push(null);
    for (let day = 1; day <= daysInMonth; day++) cells.push(new Date(year, monthIndex, day));
    while (cells.length < 42) cells.push(null);

    return cells;
}

function formatTriggerLabel(from?: string, to?: string): string | null {
    const fromDate = parseIsoDate(from);
    const toDate = parseIsoDate(to);

    if (!fromDate && !toDate) return null;
    if (fromDate && toDate) {
        const fromLabel = longDateFormatter.format(fromDate);
        const toLabel = longDateFormatter.format(toDate);

        return from === to ? fromLabel : `${fromLabel} - ${toLabel}`;
    }

    return longDateFormatter.format(fromDate ?? toDate!);
}

const resolveInitialMonth = (from?: string, to?: string) => startOfMonth(parseIsoDate(from) ?? parseIsoDate(to) ?? new Date());

export function DateRangePicker({
    from,
    to,
    onApply,
    placeholder = 'Select a range',
    className,
    disabled = false,
}: DateRangePickerProps) {
    const [open, setOpen] = useState(false);
    const [draftFrom, setDraftFrom] = useState<string | undefined>(from);
    const [draftTo, setDraftTo] = useState<string | undefined>(to);
    const [visibleMonth, setVisibleMonth] = useState<Date>(() => resolveInitialMonth(from, to));

    const appliedLabel = formatTriggerLabel(from, to);
    const rangeStart = draftFrom;
    const rangeEnd = draftTo ?? draftFrom;
    const secondMonth = useMemo(() => addMonths(visibleMonth, 1), [visibleMonth]);
    const todayIso = useMemo(() => formatIsoDate(new Date()), []);

    const handleOpenChange = (nextOpen: boolean) => {
        setDraftFrom(from);
        setDraftTo(to);
        if (nextOpen) setVisibleMonth(resolveInitialMonth(from, to));
        setOpen(nextOpen);
    };

    const handleDateSelect = (date: Date) => {
        const nextValue = formatIsoDate(date);

        if (!draftFrom || draftTo) {
            setDraftFrom(nextValue);
            setDraftTo(undefined);
            return;
        }

        if (nextValue < draftFrom) {
            setDraftFrom(nextValue);
            setDraftTo(draftFrom);
            return;
        }

        setDraftTo(nextValue);
    };

    const renderMonth = (month: Date) => (
        <div className="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
            <div className="mb-4 text-center text-lg font-semibold text-slate-800">{monthFormatter.format(month)}</div>
            <div className="grid grid-cols-7 gap-y-2 text-center text-xs font-semibold tracking-[0.12em] text-slate-400 uppercase">
                {weekdayLabels.map((label) => (
                    <div key={`${month.getMonth()}-${label}`}>{label}</div>
                ))}
            </div>
            <div className="mt-3 grid grid-cols-7 gap-y-1">
                {buildMonthGrid(month).map((date, index) => {
                    if (!date) return <div className="h-10" key={`${month.getMonth()}-empty-${index}`} />;

                    const isoValue = formatIsoDate(date);
                    const isStart = rangeStart === isoValue;
                    const isEnd = rangeEnd === isoValue;
                    const isSelected = isStart || isEnd;
                    const isInRange = !!rangeStart && !!rangeEnd && isoValue > rangeStart && isoValue < rangeEnd;

                    return (
                        <button
                            type="button"
                            key={isoValue}
                            onClick={() => handleDateSelect(date)}
                            className={cn(
                                'mx-auto flex size-10 items-center justify-center rounded-full text-sm font-medium transition-colors',
                                isSelected
                                    ? 'bg-primary text-primary-foreground'
                                    : isInRange
                                      ? 'bg-secondary text-secondary-foreground'
                                      : 'text-slate-700 hover:bg-slate-200',
                                todayIso === isoValue && !isSelected && 'border border-border',
                            )}
                        >
                            {date.getDate()}
                        </button>
                    );
                })}
            </div>
        </div>
    );

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    className={cn('w-full justify-between font-normal', !appliedLabel && 'text-muted-foreground', className)}
                >
                    <span className="truncate">{appliedLabel ?? placeholder}</span>
                    <CalendarDays className="text-muted-foreground ml-2 size-4 shrink-0" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                avoidCollisions={false}
                side="bottom"
                sideOffset={8}
                className="w-[min(720px,calc(100vw-2rem))] rounded-3xl border border-slate-200 p-0 shadow-2xl"
            >
                <div className="space-y-4 p-4">
                    <div className="flex items-center justify-between">
                        <Button type="button" variant="outline" size="icon" className="size-9 rounded-xl" onClick={() => setVisibleMonth((month) => addMonths(month, -1))}>
                            <ChevronLeft className="size-4" />
                        </Button>
                        <Button type="button" variant="outline" size="icon" className="size-9 rounded-xl" onClick={() => setVisibleMonth((month) => addMonths(month, 1))}>
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>

                    <div className="grid gap-3 lg:grid-cols-2">
                        {renderMonth(visibleMonth)}
                        {renderMonth(secondMonth)}
                    </div>

                    <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setDraftFrom(undefined);
                                setDraftTo(undefined);
                                onApply({});
                                setOpen(false);
                            }}
                        >
                            Clear
                        </Button>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                disabled={!draftFrom}
                                onClick={() => {
                                    onApply({ from: draftFrom, to: draftTo ?? draftFrom });
                                    setOpen(false);
                                }}
                            >
                                Apply
                            </Button>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
