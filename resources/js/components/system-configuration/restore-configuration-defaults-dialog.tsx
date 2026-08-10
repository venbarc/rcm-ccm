import InputError from '@/components/input-error';
import type { ConfigurationSectionData } from '@/components/system-configuration/types';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { type FormEvent } from 'react';

interface RestoreConfigurationDefaultsDialogProps {
    section: ConfigurationSectionData;
    onClose: () => void;
}

export function RestoreConfigurationDefaultsDialog({ section, onClose }: RestoreConfigurationDefaultsDialogProps) {
    const form = useForm({ confirmation: 'restore' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/system-configuration/${section.type}/restore-defaults`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="bg-secondary text-primary mb-2 flex size-11 items-center justify-center rounded-full">
                        <RotateCcw className="size-5" />
                    </div>
                    <DialogTitle>Restore {section.label} defaults?</DialogTitle>
                    <DialogDescription>
                        This restores every system-owned {section.label} name, internal value, color, and order. Missing system defaults will be
                        recreated. Administrator-created options and existing claim references will not be changed.
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <InputError message={form.errors.confirmation} />
                    <DialogFooter>
                        <Button onClick={onClose} type="button" variant="outline">
                            Cancel
                        </Button>
                        <Button disabled={form.processing} type="submit">
                            <RotateCcw />
                            {form.processing ? 'Restoring...' : 'Restore defaults'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
