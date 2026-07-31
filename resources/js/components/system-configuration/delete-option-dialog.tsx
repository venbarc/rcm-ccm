import InputError from '@/components/input-error';
import type { ConfigurationOption } from '@/components/system-configuration/types';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { AlertTriangle, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

interface DeleteConfigurationOptionDialogProps {
    option: ConfigurationOption;
    typeLabel: string;
    onClose: () => void;
}

export function DeleteConfigurationOptionDialog({ option, typeLabel, onClose }: DeleteConfigurationOptionDialogProps) {
    const form = useForm<{ confirmation: string; option?: string }>({ confirmation: '' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.delete(`/system-configuration/${option.id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="mb-2 flex size-11 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <AlertTriangle className="size-5" />
                    </div>
                    <DialogTitle>Delete {typeLabel}</DialogTitle>
                    <DialogDescription>
                        You are removing <strong className="text-foreground">{option.label}</strong> from the available options. Historical claim
                        values and configuration references will be retained.
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <InputError message={form.errors.option} />
                    <label className="grid gap-1.5 text-sm font-medium">
                        Type <span className="font-mono text-red-700">confirm</span> to continue
                        <Input
                            autoComplete="off"
                            autoFocus
                            onChange={(event) => form.setData('confirmation', event.target.value)}
                            placeholder="confirm"
                            value={form.data.confirmation}
                        />
                        <InputError message={form.errors.confirmation} />
                    </label>
                    <DialogFooter>
                        <Button onClick={onClose} type="button" variant="outline">
                            Cancel
                        </Button>
                        <Button
                            className="bg-red-700 text-white hover:bg-red-800"
                            disabled={form.processing || form.data.confirmation !== 'confirm'}
                            type="submit"
                        >
                            <Trash2 />
                            {form.processing ? 'Deleting...' : 'Delete option'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
