import { EMPTY_VALUE } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const statusClasses: Record<string, string> = {
    '#n/a': 'border-slate-300 bg-slate-50 text-slate-600',
    'appealed claim': 'border-purple-300 bg-purple-50 text-purple-800',
    'corrected claim sent': 'border-blue-300 bg-blue-50 text-blue-800',
    denied: 'border-red-300 bg-red-50 text-red-700',
    'inactive/voided': 'border-gray-300 bg-gray-100 text-gray-600',
    'payment pending': 'border-yellow-300 bg-yellow-50 text-yellow-800',
    'ready for sending': 'border-sky-300 bg-sky-50 text-sky-800',
    'resolved/other': 'border-teal-300 bg-teal-50 text-teal-800',
    'resolved/paid': 'border-green-300 bg-green-50 text-green-800',
    'review needed': 'border-orange-300 bg-orange-50 text-orange-800',
};

export function ModMedClaimStatusBadge({ className, status }: { className?: string; status: string | null }) {
    const value = status?.trim();

    if (!value) {
        return <span className={cn('text-muted-foreground', className)}>{EMPTY_VALUE}</span>;
    }

    return (
        <Badge
            className={cn(
                'gap-1.5 px-2 py-0.5 font-medium whitespace-nowrap',
                statusClasses[value.toLowerCase()] ?? 'border-indigo-300 bg-indigo-50 text-indigo-800',
                className,
            )}
            variant="outline"
        >
            <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-current opacity-70" />
            {value}
        </Badge>
    );
}
