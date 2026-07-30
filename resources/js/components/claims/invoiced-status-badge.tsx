import { EMPTY_VALUE } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const statusLabels: Record<string, string> = {
    invoiced: 'Invoiced',
};

const statusClasses: Record<string, string> = {
    invoiced: 'border-green-300 bg-green-50 text-green-800',
};

const creditReasonLabels: Record<string, string> = {
    inactive_insurance: 'Inactive Insurance',
    not_covered_by_insurance: 'Not Covered by the Insurance',
};

const creditReasonClasses: Record<string, string> = {
    inactive_insurance: 'border-amber-300 bg-amber-50 text-amber-800',
    not_covered_by_insurance: 'border-violet-300 bg-violet-50 text-violet-800',
};

export function invoicedStatusLabel(status: string | null): string {
    if (!status) {
        return EMPTY_VALUE;
    }

    return statusLabels[status] ?? status;
}

export function creditReasonLabel(reason: string | null): string {
    if (!reason) {
        return EMPTY_VALUE;
    }

    return creditReasonLabels[reason] ?? reason;
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

export function CreditStatusBadge({ className, credited }: { className?: string; credited: boolean | null }) {
    return (
        <Badge
            className={cn(
                'gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap',
                credited === true
                    ? 'border-blue-300 bg-blue-50 text-blue-800'
                    : credited === false
                      ? 'border-slate-300 bg-slate-50 text-slate-700'
                      : 'border-amber-300 bg-amber-50 text-amber-800',
                className,
            )}
            variant="outline"
        >
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {credited === null ? 'Open' : credited ? 'Yes' : 'No'}
        </Badge>
    );
}

export function CreditReasonBadge({ className, reason }: { className?: string; reason: string | null }) {
    if (!reason) {
        return <span className={cn('text-muted-foreground', className)}>{EMPTY_VALUE}</span>;
    }

    return (
        <Badge
            className={cn(
                'gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap',
                creditReasonClasses[reason] ?? 'border-slate-300 bg-slate-50 text-slate-700',
                className,
            )}
            variant="outline"
        >
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {creditReasonLabel(reason)}
        </Badge>
    );
}
