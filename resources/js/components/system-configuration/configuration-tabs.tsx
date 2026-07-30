import type { ConfigurationSectionData } from '@/components/system-configuration/types';
import { cn } from '@/lib/utils';

interface ConfigurationTabsProps {
    activeType: string;
    onValueChange: (type: string) => void;
    sections: ConfigurationSectionData[];
}

export function ConfigurationTabs({ activeType, onValueChange, sections }: ConfigurationTabsProps) {
    return (
        <div className="overflow-x-auto rounded-xl border border-blue-100 bg-white p-1.5 shadow-sm" role="tablist">
            <div className="flex min-w-max gap-1">
                {sections.map((section) => {
                    const isActive = section.type === activeType;

                    return (
                        <button
                            aria-controls={`configuration-panel-${section.type}`}
                            aria-selected={isActive}
                            className={cn(
                                'flex min-h-10 items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                                isActive ? 'bg-blue-100 text-blue-950 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950',
                            )}
                            id={`configuration-tab-${section.type}`}
                            key={section.type}
                            onClick={() => onValueChange(section.type)}
                            role="tab"
                            type="button"
                        >
                            <span>{section.label}</span>
                            <span
                                className={cn(
                                    'rounded-full px-2 py-0.5 text-xs tabular-nums',
                                    isActive ? 'bg-white/80 text-blue-900' : 'bg-slate-100 text-slate-600',
                                )}
                            >
                                {section.options.length}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
