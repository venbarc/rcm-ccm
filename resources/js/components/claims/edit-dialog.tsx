import type { ClaimGroup, ClaimLine, StatusOption } from '@/components/claims/types';
import { lineProcedureCode } from '@/components/claims/utils';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface EditFormState {
    work_status: string;
    denial_reason: string;
    notes: string;
}

interface ClaimEditDialogProps {
    claim: ClaimGroup | null;
    line: ClaimLine | null;
    editForm: EditFormState;
    setEditForm: (value: EditFormState) => void;
    workStatuses: StatusOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSave: () => void;
}

export function ClaimEditDialog({ claim, line, editForm, setEditForm, workStatuses, open, onOpenChange, onSave }: ClaimEditDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit claim line</DialogTitle>
                    <DialogDescription>Update only the selected CPT line under claim {claim?.external_id}.</DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                            <div className="text-[11px] font-semibold tracking-[0.12em] text-slate-500 uppercase">Claim ID</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{claim?.external_id ?? 'N/A'}</div>
                        </div>
                        <div className="rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                            <div className="text-[11px] font-semibold tracking-[0.12em] text-slate-500 uppercase">CPT code</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">
                                {line ? lineProcedureCode(line.procedure_code, line.cpt_code) : 'N/A'}
                            </div>
                        </div>
                    </div>
                    <label className="grid gap-1.5 text-sm font-medium">
                        Work status
                        <select
                            className="bg-background h-10 rounded-md border px-3 font-normal"
                            onChange={(event) => setEditForm({ ...editForm, work_status: event.target.value })}
                            value={editForm.work_status}
                        >
                            {workStatuses.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="grid gap-1.5 text-sm font-medium">
                        Denial reason
                        <Input onChange={(event) => setEditForm({ ...editForm, denial_reason: event.target.value })} value={editForm.denial_reason} />
                    </label>
                    <label className="grid gap-1.5 text-sm font-medium">
                        Notes
                        <textarea
                            className="bg-background min-h-28 w-full rounded-md border px-3 py-2 font-normal"
                            onChange={(event) => setEditForm({ ...editForm, notes: event.target.value })}
                            rows={5}
                            value={editForm.notes}
                        />
                    </label>
                </div>
                <DialogFooter>
                    <Button onClick={() => onOpenChange(false)} variant="outline">
                        Cancel
                    </Button>
                    <Button onClick={onSave}>Save changes</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
