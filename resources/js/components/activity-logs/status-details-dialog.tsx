import type { StatusSummaryItem, WorkedLine } from '@/components/activity-logs/types';
import { formatCurrency } from '@/components/activity-logs/types';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useClaimWorkspace } from '@/hooks/use-claim-workspace';
import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';

interface StatusDetailsDialogProps {
    status: StatusSummaryItem | null;
    lines: WorkedLine[];
    hasMore: boolean;
    isLoading: boolean;
    returnTo: string;
    onClose: () => void;
    onLoadMore: () => void;
}

export function StatusDetailsDialog({ status, lines, hasMore, isLoading, returnTo, onClose, onLoadMore }: StatusDetailsDialogProps) {
    const workspace = useClaimWorkspace();

    return (
        <Dialog open={status !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-5xl">
                <DialogHeader>
                    <DialogTitle>{status?.label} Claim Lines</DialogTitle>
                    <DialogDescription>Worked lines included in this status summary.</DialogDescription>
                </DialogHeader>
                <div className="max-h-[65vh] overflow-y-auto rounded-md border">
                    {lines.map((line) => (
                        <div className="grid gap-2 border-b p-3 text-sm last:border-0 md:grid-cols-[1fr_1fr_auto_auto] md:items-center" key={line.id}>
                            <div>
                                <p className="font-semibold">
                                    {line.claim_number} - {workspace.procedureLabel} {line.cpt_code || '-'}
                                </p>
                                <p className="text-muted-foreground text-xs">{line.patient_name || 'Unknown patient'}</p>
                            </div>
                            <p className="text-muted-foreground">{line.denial_reason || 'No denial reason'}</p>
                            {workspace.showTrueBalance && <p className="font-semibold text-rose-600">{formatCurrency(line.balance)}</p>}
                            <Button asChild size="sm" variant="outline">
                                <Link href={`/claims/${line.claim_id}?return_to=${encodeURIComponent(returnTo)}`}>
                                    <Eye />
                                    View
                                </Link>
                            </Button>
                        </div>
                    ))}
                    {isLoading && <p className="text-muted-foreground p-8 text-center text-sm">Loading claim lines...</p>}
                    {!isLoading && lines.length === 0 && <p className="text-muted-foreground p-8 text-center text-sm">No claim lines found.</p>}
                </div>
                {hasMore && (
                    <Button disabled={isLoading} variant="outline" onClick={onLoadMore}>
                        Load more
                    </Button>
                )}
            </DialogContent>
        </Dialog>
    );
}
