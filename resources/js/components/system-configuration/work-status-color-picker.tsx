import { workStatusForegroundColor } from '@/components/claims/utils';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';

interface WorkStatusColorPickerProps {
    error?: string;
    onChange: (color: string) => void;
    selectedColor: string;
    usedColors: string[];
}

export const normalizeHexColor = (color: string) => color.trim().toUpperCase();

export const isValidHexColor = (color: string) => /^#[0-9A-F]{6}$/.test(normalizeHexColor(color));

export function WorkStatusColorPicker({ error, onChange, selectedColor, usedColors }: WorkStatusColorPickerProps) {
    const normalizedColor = normalizeHexColor(selectedColor);
    const isValid = isValidHexColor(normalizedColor);
    const colorIsUsed = usedColors.some((color) => normalizeHexColor(color) === normalizedColor);
    const localError =
        normalizedColor !== '' && !isValid
            ? 'Enter a valid six-digit hex color such as #DCEEFF.'
            : colorIsUsed
              ? 'That background color is already assigned to another Work Status.'
              : undefined;
    const pickerValue = isValid ? normalizedColor : '#FFFFFF';

    return (
        <fieldset className="grid gap-3">
            <legend className="text-sm font-medium">
                Background color <span className="text-red-600">*</span>
            </legend>
            <div className="grid items-end gap-3 sm:grid-cols-[96px_1fr]">
                <label className="grid gap-1.5 text-xs font-medium text-slate-600">
                    Color picker
                    <input
                        aria-label="Choose Work Status background color"
                        className="h-10 w-full cursor-pointer rounded-md border border-slate-300 bg-white p-1"
                        onChange={(event) => onChange(normalizeHexColor(event.target.value))}
                        type="color"
                        value={pickerValue}
                    />
                </label>
                <label className="grid gap-1.5 text-xs font-medium text-slate-600">
                    Hex code
                    <Input
                        aria-invalid={Boolean(error || localError)}
                        className="font-mono uppercase"
                        maxLength={7}
                        onChange={(event) => onChange(normalizeHexColor(event.target.value))}
                        pattern="^#[0-9A-Fa-f]{6}$"
                        placeholder="#DCEEFF"
                        required
                        value={selectedColor}
                    />
                </label>
            </div>
            <div
                className="flex min-h-12 items-center justify-between rounded-lg border border-slate-200 px-3 py-2"
                style={{ backgroundColor: pickerValue, color: workStatusForegroundColor(pickerValue) }}
            >
                <span className="text-xs font-medium">Row color preview</span>
                <code className="rounded bg-white/80 px-2 py-1 text-xs text-slate-800">{isValid ? normalizedColor : 'No color selected'}</code>
            </div>
            {usedColors.length > 0 && (
                <div>
                    <p className="mb-1.5 text-xs font-medium text-slate-600">Already used colors</p>
                    <div className="flex flex-wrap gap-1.5">
                        {usedColors.map((color) => (
                            <span className="inline-flex items-center gap-1 rounded-md border bg-white px-2 py-1 font-mono text-[10px]" key={color}>
                                <span aria-hidden="true" className="size-3 rounded-sm border border-slate-300" style={{ backgroundColor: color }} />
                                {normalizeHexColor(color)}
                            </span>
                        ))}
                    </div>
                </div>
            )}
            <p className="text-muted-foreground text-xs">
                Choose any custom color or enter its six-digit hex value. Each Work Status must be unique.
            </p>
            <InputError message={error ?? localError} />
        </fieldset>
    );
}
