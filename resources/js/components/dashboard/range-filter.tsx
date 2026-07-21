import { DateRangeFilterField } from '@/components/date-range-filter-field';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { DashboardFilters } from './types';

interface DashboardRangeFilterProps {
    filters: DashboardFilters;
}

export function DashboardRangeFilter({ filters }: DashboardRangeFilterProps) {
    const [preset, setPreset] = useState(filters.preset);
    const [start, setStart] = useState(filters.start ?? '');
    const [end, setEnd] = useState(filters.end ?? '');

    const applyFilters = (nextPreset = preset, nextStart = start, nextEnd = end) => {
        const params: Record<string, string> = { preset: nextPreset };
        if (nextPreset === 'custom') {
            params.start = nextStart;
            params.end = nextEnd;
        }

        router.get('/dashboard', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const reset = () => {
        setPreset('all');
        setStart('');
        setEnd('');
        applyFilters('all', '', '');
    };

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-end gap-4">
                <div className="flex min-w-52 flex-1 flex-col gap-2">
                    <Label htmlFor="dashboard-preset">Range</Label>
                    <Select
                        value={preset}
                        onValueChange={(value) => {
                            setPreset(value);
                            if (value !== 'custom') {
                                setStart('');
                                setEnd('');
                                applyFilters(value, '', '');
                            }
                        }}
                    >
                        <SelectTrigger id="dashboard-preset">
                            <SelectValue placeholder="Select range" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="week">This week</SelectItem>
                            <SelectItem value="month">This month</SelectItem>
                            <SelectItem value="year">This year</SelectItem>
                            <SelectItem value="all">All time</SelectItem>
                            <SelectItem value="custom">Custom range</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <DateRangeFilterField
                    className="min-w-72 flex-[2]"
                    disabled={preset !== 'custom'}
                    from={start}
                    label="Service Date Range"
                    onApply={({ from: nextStart, to: nextEnd }) => {
                        if (!nextStart && !nextEnd) {
                            reset();
                            return;
                        }

                        setPreset('custom');
                        setStart(nextStart);
                        setEnd(nextEnd);
                        applyFilters('custom', nextStart, nextEnd);
                    }}
                    placeholder="Select service date range"
                    to={end}
                />
                <div className="flex gap-2">
                    <Button onClick={() => applyFilters()} disabled={preset === 'custom' && (!start || !end)}>
                        Apply
                    </Button>
                    <Button variant="ghost" onClick={reset}>
                        Reset
                    </Button>
                </div>
            </div>
            <p className="text-muted-foreground text-xs">
                Showing{' '}
                <span className="text-foreground font-medium">
                    {filters.presetLabel === filters.label ? filters.label : `${filters.presetLabel} - ${filters.label}`}
                </span>
            </p>
        </div>
    );
}
