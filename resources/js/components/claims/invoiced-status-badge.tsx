import { EMPTY_VALUE } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const statusLabels: Record<string, string> = {
    invoiced: 'Invoiced',
};

const statusClasses: Record<string, string> = {
    invoiced: 'border-green-300 bg-green-50 text-green-800',
};

const creditReasonClasses = [
    'border-amber-300 bg-amber-50 text-amber-800',
    'border-violet-300 bg-violet-50 text-violet-800',
    'border-border bg-secondary text-secondary-foreground',
    'border-emerald-300 bg-emerald-50 text-emerald-800',
];

const EMPTY_CREDIT_STATUS = '--';

function creditReasonClass(reason: string): string {
    const colorIndex = Array.from(reason).reduce((total, character) => total + character.charCodeAt(0), 0) % creditReasonClasses.length;

    return creditReasonClasses[colorIndex];
}

export function invoicedStatusLabel(status: string | null): string {
    if (!status) {
        return EMPTY_VALUE;
    }

    return statusLabels[status] ?? status;
}

export function creditReasonLabel(reason: string | null, label?: string | null): string {
    if (!reason) {
        return EMPTY_VALUE;
    }

    return label || reason;
}

export function InvoicedStatusBadge({ className, status }: { className?: string; status: string | null }) {
    if (!status) {
        return <span className={cn('text-muted-foreground', className)}>{EMPTY_VALUE}</span>;
    }

    return (
        <Badge
            className={cn('gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap', statusClasses[status] ?? 'border-slate-300', className)}
            variant="outline"
        >
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {invoicedStatusLabel(status)}
        </Badge>
    );
}

export function CreditStatusBadge({ className, credited, label }: { className?: string; credited: boolean | null; label?: string | null }) {
    if (credited === null) {
        return <span className={cn('text-muted-foreground', className)}>{EMPTY_CREDIT_STATUS}</span>;
    }

    return (
        <Badge
            className={cn(
                'gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap',
                credited ? 'border-border bg-secondary text-secondary-foreground' : 'border-slate-300 bg-slate-50 text-slate-700',
                className,
            )}
            variant="outline"
        >
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {label || (credited ? 'Yes' : 'No')}
        </Badge>
    );
}

export function CreditReasonBadge({ className, label, reason }: { className?: string; label?: string | null; reason: string | null }) {
    if (!reason) {
        return <span className={cn('text-muted-foreground', className)}>{EMPTY_VALUE}</span>;
    }

    return (
        <Badge className={cn('gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap', creditReasonClass(reason), className)} variant="outline">
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {creditReasonLabel(reason, label)}
        </Badge>
    );
}
