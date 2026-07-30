import InputError from '@/components/input-error';
import { ConfigurationColorPicker, isValidHexColor, normalizeHexColor } from '@/components/system-configuration/configuration-color-picker';
import type { ConfigurationOption } from '@/components/system-configuration/types';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { type FormEvent } from 'react';

interface ConfigurationOptionDialogProps {
    optionType: string;
    typeLabel: string;
    option: ConfigurationOption | null;
    onClose: () => void;
    usedColors: string[];
}

export function ConfigurationOptionDialog({ optionType, typeLabel, option, onClose, usedColors }: ConfigurationOptionDialogProps) {
    const isWorkStatus = optionType === 'work_status';
    const usesColor = isWorkStatus || optionType === 'modmed_claim_status';
    const form = useForm({
        option_type: optionType,
        label: option?.label ?? '',
        color: option?.color ?? '',
    });
    const normalizedColor = normalizeHexColor(form.data.color);
    const colorIsUsed = usedColors.some((color) => normalizeHexColor(color) === normalizedColor);
    const colorIsValid = isValidHexColor(normalizedColor);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: onClose,
        };

        if (option) {
            form.patch(`/system-configuration/${option.id}`, options);
        } else {
            form.post('/system-configuration', options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{option ? `Edit ${typeLabel}` : `Add ${typeLabel}`}</DialogTitle>
                    <DialogDescription>
                        {option
                            ? `Update the display name${usesColor ? ' and color' : ''}. Existing claim records keep their stable internal value.`
                            : `Add a new ${typeLabel.toLowerCase()} option for the active account.`}
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <label className="grid gap-1.5 text-sm font-medium">
                        Name <span className="sr-only">(required)</span>
                        <Input
                            autoFocus
                            maxLength={255}
                            onChange={(event) => form.setData('label', event.target.value)}
                            placeholder={`Enter ${typeLabel.toLowerCase()}`}
                            required
                            value={form.data.label}
                        />
                        <InputError message={form.errors.label} />
                    </label>
                    {usesColor && (
                        <ConfigurationColorPicker
                            error={form.errors.color}
                            onChange={(color) => form.setData('color', color)}
                            previewLabel={isWorkStatus ? 'Row color preview' : 'Badge color preview'}
                            selectedColor={form.data.color}
                            typeLabel={typeLabel}
                            usedColors={usedColors}
                        />
                    )}
                    {option && (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            Internal value: <span className="font-mono font-medium text-slate-900">{option.value}</span>
                        </div>
                    )}
                    <DialogFooter>
                        <Button onClick={onClose} type="button" variant="outline">
                            Cancel
                        </Button>
                        <Button
                            disabled={form.processing || form.data.label.trim() === '' || (usesColor && (!colorIsValid || colorIsUsed))}
                            type="submit"
                        >
                            <Save />
                            {form.processing ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
