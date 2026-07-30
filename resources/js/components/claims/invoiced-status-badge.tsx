import { EMPTY_VALUE } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const statusLabels: Record<string, string> = {
    invoiced: 'Invoiced',
    pending_credit: 'Pending Credit',
    credited: 'Credited',
};

const statusClasses: Record<string, string> = {
    invoiced: 'border-green-300 bg-green-50 text-green-800',
    pending_credit: 'border-amber-300 bg-amber-50 text-amber-800',
    credited: 'border-blue-300 bg-blue-50 text-blue-800',
};

const creditReasonLabels: Record<string, string> = {
    inactive_insurance: 'Inactive Insurance',
    not_covered_by_insurance: 'Not Covered by the Insurance',
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
