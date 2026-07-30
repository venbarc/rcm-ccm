import type { ClaimGroup, ClaimLine, StatusOption } from '@/components/claims/types';
import { lineProcedureCode } from '@/components/claims/utils';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface EditFormState {
    work_status: string;
    denial_reason: string;
    notes: string;
    invoiced_status: string;
    invoiced_status_date: string;
    credit_reason: string;
}

interface ClaimEditDialogProps {
    claim: ClaimGroup | null;
    line: ClaimLine | null;
    editForm: EditFormState;
    setEditForm: (value: EditFormState) => void;
    workStatuses: StatusOption[];
    invoicedStatuses: StatusOption[];
    creditReasons: StatusOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSave: () => void;
}

export function ClaimEditDialog({
    claim,
    line,
    editForm,
    setEditForm,
    workStatuses,
    invoicedStatuses,
    creditReasons,
    open,
    onOpenChange,
    onSave,
}: ClaimEditDialogProps) {
    const needsCreditReason = ['pending_credit', 'credited'].includes(editForm.invoiced_status);
    const invoicingFieldsComplete =
        editForm.invoiced_status === '' || (editForm.invoiced_status_date !== '' && (!needsCreditReason || editForm.credit_reason !== ''));

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit claim line</DialogTitle>
                    <DialogDescription>Update only the selected CPT line under Bill ID {claim?.bill_id}.</DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                            <div className="text-[11px] font-semibold tracking-[0.12em] text-slate-500 uppercase">Bill ID</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{claim?.bill_id ?? 'N/A'}</div>
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
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="grid gap-1.5 text-sm font-medium">
                            Invoiced Status
                            <select
                                className="bg-background h-10 rounded-md border px-3 font-normal"
                                onChange={(event) => {
                                    const status = event.target.value;
                                    setEditForm({
                                        ...editForm,
                                        invoiced_status: status,
                                        invoiced_status_date: status ? editForm.invoiced_status_date : '',
                                        credit_reason: ['pending_credit', 'credited'].includes(status) ? editForm.credit_reason : '',
                                    });
                                }}
                                value={editForm.invoiced_status}
                            >
                                <option value="">Not set</option>
                                {invoicedStatuses.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="grid gap-1.5 text-sm font-medium">
                            Invoiced Status Date
                            <Input
                                disabled={!editForm.invoiced_status}
                                onChange={(event) => setEditForm({ ...editForm, invoiced_status_date: event.target.value })}
                                required={Boolean(editForm.invoiced_status)}
                                type="date"
                                value={editForm.invoiced_status_date}
                            />
                        </label>
                    </div>
                    {needsCreditReason && (
                        <label className="grid gap-1.5 text-sm font-medium">
                            Credit Reason
                            <select
                                className="bg-background h-10 rounded-md border px-3 font-normal"
                                onChange={(event) => setEditForm({ ...editForm, credit_reason: event.target.value })}
                                required
                                value={editForm.credit_reason}
                            >
                                <option value="">Select a credit reason</option>
                                {creditReasons.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                    )}
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
                    <Button disabled={!invoicingFieldsComplete} onClick={onSave}>
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
