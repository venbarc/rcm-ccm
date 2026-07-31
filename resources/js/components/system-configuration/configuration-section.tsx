import { TruncatedTooltipText } from '@/components/system-configuration/truncated-tooltip-text';
import type { ConfigurationOption, ConfigurationSectionData } from '@/components/system-configuration/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react';

interface ConfigurationSectionProps {
    section: ConfigurationSectionData;
    onCreate: (section: ConfigurationSectionData) => void;
    onDelete: (section: ConfigurationSectionData, option: ConfigurationOption) => void;
    onEdit: (section: ConfigurationSectionData, option: ConfigurationOption) => void;
    onRestoreDefaults: () => void;
}

const dateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '-');

export function ConfigurationSection({ section, onCreate, onDelete, onEdit, onRestoreDefaults }: ConfigurationSectionProps) {
    const showsColor = section.type === 'work_status' || section.type === 'modmed_claim_status';
    const hasFixedValues = section.type === 'credit_status';

    return (
        <Card className="border-blue-100">
            <CardHeader className="flex flex-row items-center justify-between gap-3 border-b">
                <div>
                    <CardTitle className="text-lg">{section.label}</CardTitle>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {section.options.length} configured option{section.options.length === 1 ? '' : 's'}
                    </p>
                </div>
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {section.can_restore_defaults && (
                        <Button onClick={onRestoreDefaults} size="sm" variant="outline">
                            <RotateCcw />
                            Restore system defaults
                        </Button>
                    )}
                    {!hasFixedValues && (
                        <Button onClick={() => onCreate(section)} size="sm">
                            <Plus />
                            Add {section.label}
                        </Button>
                    )}
                </div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className={showsColor ? 'w-full min-w-[900px] table-fixed text-sm' : 'w-full min-w-[760px] table-fixed text-sm'}>
                        <thead className="bg-slate-50 text-left text-slate-600">
                            <tr>
                                <th className="w-[24%] px-4 py-3 font-medium">Name</th>
                                <th className="w-[21%] px-4 py-3 font-medium">Internal Value</th>
                                {showsColor && <th className="w-44 px-4 py-3 font-medium">Color</th>}
                                <th className="w-[20%] px-4 py-3 font-medium">Added By</th>
                                <th className="w-44 px-4 py-3 font-medium">Added At</th>
                                <th className="w-24 px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {section.options.map((option) => {
                                const isDefaultDraft = section.type === 'work_status' && option.value === 'draft';
                                const cannotDelete = isDefaultDraft || hasFixedValues;

                                return (
                                    <tr className="hover:bg-slate-50/70" key={option.id}>
                                        <td className="min-w-0 px-4 py-3">
                                            <div className="flex min-w-0 items-center gap-2">
                                                <TruncatedTooltipText
                                                    className="min-w-0 flex-1"
                                                    textClassName="font-medium text-slate-950"
                                                    value={option.label}
                                                />
                                                {isDefaultDraft && (
                                                    <Badge className="shrink-0 border-blue-200 bg-blue-50 text-blue-800" variant="outline">
                                                        Default
                                                    </Badge>
                                                )}
                                                {hasFixedValues && (
                                                    <Badge className="shrink-0 border-blue-200 bg-blue-50 text-blue-800" variant="outline">
                                                        Required
                                                    </Badge>
                                                )}
                                            </div>
                                        </td>
                                        <td className="min-w-0 px-4 py-3">
                                            <TruncatedTooltipText
                                                textClassName="rounded bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700"
                                                value={option.value}
                                            />
                                        </td>
                                        {showsColor && (
                                            <td className="px-4 py-3">
                                                {option.color ? (
                                                    <div className="flex items-center gap-2">
                                                        <span
                                                            aria-hidden="true"
                                                            className="size-7 shrink-0 rounded-md border border-slate-300"
                                                            style={{ backgroundColor: option.color }}
                                                        />
                                                        <code className="text-xs text-slate-700">{option.color}</code>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </td>
                                        )}
                                        <td className="px-4 py-3">
                                            {option.added_by ? (
                                                <div>
                                                    <p className="font-medium">{option.added_by.name}</p>
                                                    <p className="text-muted-foreground text-xs">{option.added_by.email}</p>
                                                </div>
                                            ) : (
                                                <Badge className="border-slate-300 bg-slate-50 text-slate-700" variant="outline">
                                                    System
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3 whitespace-nowrap">{dateTime(option.created_at)}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    aria-label={`Edit ${option.label}`}
                                                    disabled={isDefaultDraft}
                                                    onClick={() => onEdit(section, option)}
                                                    size="icon"
                                                    title={
                                                        isDefaultDraft
                                                            ? 'Draft is the required default and cannot be edited.'
                                                            : `Edit ${option.label}`
                                                    }
                                                    variant="ghost"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    aria-label={`Delete ${option.label}`}
                                                    className="text-red-700 hover:bg-red-50 hover:text-red-800"
                                                    disabled={cannotDelete}
                                                    onClick={() => onDelete(section, option)}
                                                    size="icon"
                                                    title={
                                                        cannotDelete
                                                            ? `${option.label} is a required ${section.label} option and cannot be deleted.`
                                                            : `Delete ${option.label}`
                                                    }
                                                    variant="ghost"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {section.options.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-4 py-10 text-center" colSpan={showsColor ? 6 : 5}>
                                        No {section.label.toLowerCase()} options configured yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
