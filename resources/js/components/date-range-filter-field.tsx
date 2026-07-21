import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface DateRangeFilterFieldProps {
    label: string;
    from: string;
    to: string;
    onApply: (range: { from: string; to: string }) => void;
    placeholder?: string;
    disabled?: boolean;
    hideLabel?: boolean;
    className?: string;
    pickerClassName?: string;
}

export function DateRangeFilterField({
    label,
    from,
    to,
    onApply,
    placeholder = 'Select date range',
    disabled = false,
    hideLabel = false,
    className,
    pickerClassName,
}: DateRangeFilterFieldProps) {
    return (
        <div className={cn('flex flex-col gap-2', className)}>
            <Label className={hideLabel ? 'sr-only' : undefined}>{label}</Label>
            <DateRangePicker
                className={pickerClassName}
                disabled={disabled}
                from={from || undefined}
                onApply={({ from: nextFrom, to: nextTo }) => onApply({ from: nextFrom ?? '', to: nextTo ?? '' })}
                placeholder={placeholder}
                to={to || undefined}
            />
        </div>
    );
}
