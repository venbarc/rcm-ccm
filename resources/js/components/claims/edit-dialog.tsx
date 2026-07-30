import type { ClaimGroup, ClaimLine, StatusOption } from '@/components/claims/types';
import { lineProcedureCode } from '@/components/claims/utils';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface EditFormState {
    work_status: string;
    denial_reason: string;
    notes: string;
    credit_status: '' | 'yes' | 'no';
    credit_status_date: string;
    credit_reason: string;
}

interface ClaimEditDialogProps {
    claim: ClaimGroup | null;
    line: ClaimLine | null;
    editForm: EditFormState;
    setEditForm: (value: EditFormState) => void;
    workStatuses: StatusOption[];
    creditStatuses: StatusOption[];
    creditReasons: StatusOption[];
    denialReasons: StatusOption[];
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
    creditStatuses,
    creditReasons,
    denialReasons,
    open,
    onOpenChange,
    onSave,
}: ClaimEditDialogProps) {
    const isCredited = editForm.credit_status === 'yes';
    const affirmativeCreditStatusLabel = creditStatuses.find((item) => item.value === 'yes')?.label ?? 'Yes';
    const isCreditStatusDateMissing = isCredited && editForm.credit_status_date === '';
    const isCreditReasonMissing = isCredited && editForm.credit_reason === '';
    const creditFieldsComplete = !isCreditStatusDateMissing && !isCreditReasonMissing;

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
                            {!workStatuses.some((item) => item.value === editForm.work_status) && editForm.work_status && (
                                <option value={editForm.work_status}>{editForm.work_status}</option>
                            )}
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
                            <Input className="bg-muted text-muted-foreground" readOnly value="Invoiced" />
                        </label>
                        <label className="grid gap-1.5 text-sm font-medium">
                            Invoiced Status Date
                            <Input className="bg-muted text-muted-foreground" readOnly type="date" value={line?.cf_invoice_date ?? ''} />
                        </label>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="grid gap-1.5 text-sm font-medium">
                            Credit Status
                            <select
                                className="bg-background h-10 rounded-md border px-3 font-normal"
                                onChange={(event) => {
                                    const status = event.target.value as '' | 'yes' | 'no';
                                    setEditForm({
                                        ...editForm,
                                        credit_status: status,
                                        credit_status_date: status === 'yes' ? editForm.credit_status_date : '',
                                        credit_reason: status === 'yes' ? editForm.credit_reason : '',
                                    });
                                }}
                                value={editForm.credit_status}
                            >
                                <option value="">--</option>
                                {creditStatuses
                                    .filter((item) => item.value === 'yes' || item.value === 'no')
                                    .map((item) => (
                                        <option key={item.value} value={item.value}>
                                            {item.label}
                                        </option>
                                    ))}
                            </select>
                        </label>
                        <label className="grid gap-1.5 text-sm font-medium">
                            <span>
                                Credit Status Date
                                {isCredited && (
                                    <span aria-label="required" className="font-bold text-red-600">
                                        {' '}
                                        *
                                    </span>
                                )}
                            </span>
                            <Input
                                aria-invalid={isCreditStatusDateMissing}
                                aria-required={isCredited}
                                className={isCreditStatusDateMissing ? 'border-destructive' : undefined}
                                disabled={!isCredited}
                                onChange={(event) => setEditForm({ ...editForm, credit_status_date: event.target.value })}
                                required={isCredited}
                                type="date"
                                value={editForm.credit_status_date}
                            />
                            {isCreditStatusDateMissing && (
                                <span className="text-destructive text-xs">
                                    Credit Status Date is required when Credit Status is {affirmativeCreditStatusLabel}.
                                </span>
                            )}
                        </label>
                    </div>
                    {isCredited && (
                        <label className="grid gap-1.5 text-sm font-medium">
                            <span>
                                Credit Reason{' '}
                                <span aria-label="required" className="font-bold text-red-600">
                                    *
                                </span>
                            </span>
                            <select
                                aria-invalid={isCreditReasonMissing}
                                aria-required="true"
                                className={`bg-background h-10 rounded-md border px-3 font-normal ${
                                    isCreditReasonMissing ? 'border-destructive' : ''
                                }`}
                                onChange={(event) => setEditForm({ ...editForm, credit_reason: event.target.value })}
                                required
                                value={editForm.credit_reason}
                            >
                                <option value="">Select a credit reason</option>
                                {!creditReasons.some((item) => item.value === editForm.credit_reason) && editForm.credit_reason && (
                                    <option value={editForm.credit_reason}>{editForm.credit_reason}</option>
                                )}
                                {creditReasons.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                            {isCreditReasonMissing && (
                                <span className="text-destructive text-xs">
                                    Credit Reason is required when Credit Status is {affirmativeCreditStatusLabel}.
                                </span>
                            )}
                        </label>
                    )}
                    <label className="grid gap-1.5 text-sm font-medium">
                        Denial reason
                        <select
                            className="bg-background h-10 rounded-md border px-3 font-normal"
                            onChange={(event) => setEditForm({ ...editForm, denial_reason: event.target.value })}
                            value={editForm.denial_reason}
                        >
                            <option value="">No denial reason</option>
                            {!denialReasons.some((item) => item.value === editForm.denial_reason) && editForm.denial_reason && (
                                <option value={editForm.denial_reason}>{editForm.denial_reason}</option>
                            )}
                            {denialReasons.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
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
                    <Button
                        disabled={!creditFieldsComplete}
                        onClick={onSave}
                        title={!creditFieldsComplete ? 'Complete the required credit fields before saving.' : undefined}
                    >
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
