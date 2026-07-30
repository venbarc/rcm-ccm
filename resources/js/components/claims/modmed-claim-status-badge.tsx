import { EMPTY_VALUE, workStatusBadgeStyle } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function ModMedClaimStatusBadge({
    className,
    color,
    label,
    status,
}: {
    className?: string;
    color?: string | null;
    label?: string | null;
    status: string | null;
}) {
    const value = status?.trim();

    if (!value) {
        return <span className={cn('text-muted-foreground', className)}>{EMPTY_VALUE}</span>;
    }

    return (
        <Badge
            className={cn('gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap', !color && 'border-slate-300 bg-slate-50 text-slate-700', className)}
            style={color ? workStatusBadgeStyle(color) : undefined}
            variant="outline"
        >
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {label || value}
        </Badge>
    );
}
