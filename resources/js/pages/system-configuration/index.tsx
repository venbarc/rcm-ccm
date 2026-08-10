import { ConfigurationSection } from '@/components/system-configuration/configuration-section';
import { ConfigurationTabs } from '@/components/system-configuration/configuration-tabs';
import { DeleteConfigurationOptionDialog } from '@/components/system-configuration/delete-option-dialog';
import { ConfigurationOptionDialog } from '@/components/system-configuration/option-dialog';
import { RestoreConfigurationDefaultsDialog } from '@/components/system-configuration/restore-configuration-defaults-dialog';
import type { ConfigurationOption, ConfigurationSectionData } from '@/components/system-configuration/types';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Settings2, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';

interface SystemConfigurationIndexProps {
    sections: ConfigurationSectionData[];
}

interface OptionDialogState {
    section: ConfigurationSectionData;
    option: ConfigurationOption | null;
}

interface DeleteDialogState {
    section: ConfigurationSectionData;
    option: ConfigurationOption;
}

export default function SystemConfigurationIndex({ sections }: SystemConfigurationIndexProps) {
    const [activeType, setActiveType] = useState(() => sections.find((section) => section.type === 'work_status')?.type ?? sections[0]?.type ?? '');
    const [optionDialog, setOptionDialog] = useState<OptionDialogState | null>(null);
    const [deleteDialog, setDeleteDialog] = useState<DeleteDialogState | null>(null);
    const [restoreDefaultsSection, setRestoreDefaultsSection] = useState<ConfigurationSectionData | null>(null);
    const activeSection = sections.find((section) => section.type === activeType) ?? sections[0];

    return (
        <AppLayout breadcrumbs={[{ title: 'System Configuration', href: '/system-configuration' }]}>
            <Head title="System Configuration" />
            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-primary mb-1 flex items-center gap-1.5 text-xs font-semibold tracking-[0.2em] uppercase">
                            <Settings2 className="size-3.5" />
                            Administration
                        </p>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-950">System Configuration</h1>
                        <p className="text-muted-foreground text-sm">Manage the claim workflow options available throughout the active account.</p>
                    </div>
                </div>

                <Card className="border-border bg-secondary/60">
                    <CardContent className="text-secondary-foreground flex items-start gap-3 p-4 text-sm">
                        <div className="text-primary rounded-lg bg-white p-2">
                            <SlidersHorizontal className="size-5" />
                        </div>
                        <div>
                            <p className="font-semibold">Account-specific configuration</p>
                            <p className="text-secondary-foreground/80">
                                Changes apply to the active account. System entries were created during migration; administrator-created entries
                                retain the creator in the Added By column.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <ConfigurationTabs activeType={activeSection?.type ?? ''} onValueChange={setActiveType} sections={sections} />

                {activeSection && (
                    <div aria-labelledby={`configuration-tab-${activeSection.type}`} id={`configuration-panel-${activeSection.type}`} role="tabpanel">
                        <ConfigurationSection
                            onCreate={(selectedSection) => setOptionDialog({ section: selectedSection, option: null })}
                            onDelete={(selectedSection, option) => setDeleteDialog({ section: selectedSection, option })}
                            onEdit={(selectedSection, option) => setOptionDialog({ section: selectedSection, option })}
                            onRestoreDefaults={() => setRestoreDefaultsSection(activeSection)}
                            section={activeSection}
                        />
                    </div>
                )}
            </div>

            {optionDialog && (
                <ConfigurationOptionDialog
                    key={`${optionDialog.section.type}-${optionDialog.option?.id ?? 'new'}`}
                    onClose={() => setOptionDialog(null)}
                    option={optionDialog.option}
                    optionType={optionDialog.section.type}
                    typeLabel={optionDialog.section.label}
                    usedColors={optionDialog.section.options
                        .filter((option) => option.id !== optionDialog.option?.id)
                        .map((option) => option.color)
                        .filter((color): color is string => color !== null)}
                />
            )}
            {deleteDialog && (
                <DeleteConfigurationOptionDialog
                    key={deleteDialog.option.id}
                    onClose={() => setDeleteDialog(null)}
                    option={deleteDialog.option}
                    typeLabel={deleteDialog.section.label}
                />
            )}
            {restoreDefaultsSection && (
                <RestoreConfigurationDefaultsDialog onClose={() => setRestoreDefaultsSection(null)} section={restoreDefaultsSection} />
            )}
        </AppLayout>
    );
}
