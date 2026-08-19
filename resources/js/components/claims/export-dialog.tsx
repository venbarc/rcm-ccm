import { type Filters, type UserOption } from '@/components/claims/types';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { businessTimeZone } from '@/lib/date-time';
import { Clock3, Download, FileSpreadsheet, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

type ExportStatus = 'queued' | 'processing' | 'completed' | 'failed';

interface ClaimExport {
    id: number;
    file_name: string;
    status: ExportStatus;
    total_rows: number;
    processed_rows: number;
    progress: number;
    error_message: string | null;
    completed_at: string | null;
    started_by: UserOption | null;
}

interface ClaimsExportDialogProps {
    filters: Filters;
    hasActiveImport: boolean;
    onOpenChange: (open: boolean) => void;
    open: boolean;
}

interface ExportResponse {
    export: ClaimExport | null;
    exports?: ClaimExport[];
    message?: string;
    errors?: Record<string, string[]>;
}

const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

// Every key here must stay in sync with ClaimFilterService::FILTER_KEYS so the export
// mirrors the Claims page exactly.
const exportFilterLabels: Record<string, string> = {
    search: 'Search',
    modmed_claim_status: 'ModMed status',
    invoiced_status: 'Invoiced status',
    credit_status: 'Credit status',
    credit_reason: 'Credit reason',
    payer_name: 'Payer',
    primary_provider: 'Primary provider',
    location: 'Location',
    denial_reason: 'Denial reason',
    work_status: 'Work status',
    assigned_to: 'Assignee',
    worked_from: 'Worked from',
    worked_to: 'Worked to',
    service_date_from: 'Service date from',
    service_date_to: 'Service date to',
    service_month: 'Service month',
    cf_invoice_from: 'CF invoice date from',
    cf_invoice_to: 'CF invoice date to',
    credit_status_from: 'Credit status date from',
    credit_status_to: 'Credit status date to',
    procedure_code: 'CPT code',
};
const exportFilterKeys = Object.keys(exportFilterLabels);

const filterValueText = (value: string) => {
    try {
        const parsed: unknown = JSON.parse(value);

        if (Array.isArray(parsed)) {
            return parsed.join(', ');
        }
    } catch {
        // Plain filter values are not JSON encoded.
    }

    return value;
};

const responseMessage = (data: ExportResponse, fallback: string) =>
    data.message ??
    Object.values(data.errors ?? {})
        .flat()
        .at(0) ??
    fallback;

const formatDate = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: businessTimeZone,
    }).format(new Date(value));
};

export function ClaimsExportDialog({ filters, hasActiveImport, onOpenChange, open }: ClaimsExportDialogProps) {
    const [activeExport, setActiveExport] = useState<ClaimExport | null>(null);
    const [history, setHistory] = useState<ClaimExport[]>([]);
    const [showHistory, setShowHistory] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [isStarting, setIsStarting] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;
        void Promise.resolve().then(() => {
            if (!cancelled) {
                setIsLoading(true);
            }
        });

        void Promise.all([
            fetch('/claims-export/active', { headers: { Accept: 'application/json' } }).then((response) => response.json()),
            fetch('/claims-export/history', { headers: { Accept: 'application/json' } }).then((response) => response.json()),
        ])
            .then(([activeData, historyData]: [ExportResponse, ExportResponse]) => {
                if (!cancelled) {
                    setActiveExport(activeData.export);
                    setHistory(historyData.exports ?? []);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    toast.error('Export information could not be loaded.');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [open]);

    useEffect(() => {
        if (!open || !activeExport || !['queued', 'processing'].includes(activeExport.status)) {
            return;
        }

        let cancelled = false;
        const poll = window.setInterval(() => {
            void fetch(`/claims-export/${activeExport.id}/progress`, {
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((data: ExportResponse) => {
                    if (cancelled || !data.export) {
                        return;
                    }

                    setActiveExport(data.export);
                    if (data.export.status === 'completed') {
                        setHistory((current) => [data.export as ClaimExport, ...current.filter((item) => item.id !== data.export?.id)]);
                        toast.success('Claims export is ready.');
                    } else if (data.export.status === 'failed') {
                        toast.error(data.export.error_message || 'The claims export failed.');
                    }
                })
                .catch(() => undefined);
        }, 1500);

        return () => {
            cancelled = true;
            window.clearInterval(poll);
        };
    }, [activeExport, open]);

    const appliedFilters = exportFilterKeys
        .map((key) => ({ key, value: typeof filters[key] === 'string' ? filters[key].trim() : '' }))
        .filter((entry) => entry.value !== '');

    const startExport = async () => {
        setIsStarting(true);

        try {
            const response = await fetch('/claims-export/start', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    filters: Object.fromEntries(appliedFilters.map((entry) => [entry.key, entry.value])),
                }),
            });
            const data = (await response.json()) as ExportResponse;

            if (!response.ok || !data.export) {
                throw new Error(responseMessage(data, 'The claims export could not be started.'));
            }

            setActiveExport(data.export);
            if (data.export.status === 'completed') {
                setHistory((current) => [data.export as ClaimExport, ...current.filter((item) => item.id !== data.export?.id)]);
                toast.success('Claims export is ready.');
            } else {
                toast.info('Claims export started. You can keep working while it runs.');
            }
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'The claims export could not be started.');
        } finally {
            setIsStarting(false);
        }
    };

    const changeOpen = (nextOpen: boolean) => {
        if (!nextOpen) {
            setShowHistory(false);
        }

        onOpenChange(nextOpen);
    };
    const running = activeExport && ['queued', 'processing'].includes(activeExport.status);

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Download className="size-5" />
                        Export Claims
                    </DialogTitle>
                    <DialogDescription>
                        {showHistory
                            ? 'Download a completed export.'
                            : 'Exports every CPT line matching the current Claims page filters, including results outside the current page.'}
                    </DialogDescription>
                </DialogHeader>

                {isLoading ? (
                    <div className="flex min-h-40 items-center justify-center">
                        <Loader2 className="text-muted-foreground size-6 animate-spin" />
                    </div>
                ) : showHistory ? (
                    <div className="max-h-80 space-y-2 overflow-y-auto py-2">
                        {history.length === 0 ? (
                            <p className="text-muted-foreground py-10 text-center text-sm">No completed exports yet.</p>
                        ) : (
                            history.map((item) => (
                                <div key={item.id} className="flex items-center justify-between gap-3 rounded-lg border p-3">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <FileSpreadsheet className="size-5 shrink-0 text-emerald-600" />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">{item.file_name}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {item.total_rows.toLocaleString()} rows
                                                {item.completed_at ? ` | ${formatDate(item.completed_at)}` : ''}
                                            </p>
                                        </div>
                                    </div>
                                    <Button size="icon" variant="outline" asChild>
                                        <a href={`/claims-export/${item.id}/download`} aria-label={`Download ${item.file_name}`}>
                                            <Download className="size-4" />
                                        </a>
                                    </Button>
                                </div>
                            ))
                        )}
                    </div>
                ) : (
                    <div className="space-y-4 py-2">
                        {hasActiveImport && (
                            <p className="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                Export is available after the active claims import finishes.
                            </p>
                        )}

                        {activeExport && (
                            <div className="bg-secondary text-secondary-foreground space-y-2 rounded-lg p-3">
                                <div className="flex items-center justify-between gap-3 text-sm">
                                    <span className="font-medium">
                                        {running ? 'Export in progress' : activeExport.status === 'completed' ? 'Export ready' : 'Export failed'}
                                    </span>
                                    <span className="tabular-nums">{activeExport.progress}%</span>
                                </div>
                                <div
                                    className="bg-secondary-foreground/15 h-1.5 overflow-hidden rounded-full"
                                    role="progressbar"
                                    aria-valuemin={0}
                                    aria-valuemax={100}
                                    aria-valuenow={activeExport.progress}
                                >
                                    <div className="bg-primary h-full transition-[width]" style={{ width: `${activeExport.progress}%` }} />
                                </div>
                                <p className="text-xs">
                                    {activeExport.processed_rows.toLocaleString()} of {activeExport.total_rows.toLocaleString()} claim lines
                                </p>
                                {activeExport.status === 'completed' && (
                                    <Button size="sm" variant="outline" asChild>
                                        <a href={`/claims-export/${activeExport.id}/download`}>
                                            <Download className="size-4" />
                                            Download
                                        </a>
                                    </Button>
                                )}
                                {activeExport.status === 'failed' && activeExport.error_message && (
                                    <p className="text-sm text-red-700">{activeExport.error_message}</p>
                                )}
                            </div>
                        )}

                        <div className="rounded-lg border p-3">
                            <p className="text-xs font-semibold tracking-wide uppercase">Filters applied</p>
                            {appliedFilters.length === 0 ? (
                                <p className="text-muted-foreground mt-2 text-sm">
                                    No filters are applied, so every CPT line in this workspace will be exported.
                                </p>
                            ) : (
                                <ul className="mt-2 space-y-1 text-sm">
                                    {appliedFilters.map((entry) => (
                                        <li className="flex flex-wrap gap-x-1" key={entry.key}>
                                            <span className="text-muted-foreground">{exportFilterLabels[entry.key]}:</span>
                                            <span className="font-medium break-all">{filterValueText(entry.value)}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                )}

                <DialogFooter className="gap-2 sm:justify-between">
                    {showHistory ? (
                        <>
                            <Button variant="outline" onClick={() => setShowHistory(false)}>
                                Back to Export
                            </Button>
                            <Button variant="outline" onClick={() => changeOpen(false)}>
                                Close
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button variant="ghost" onClick={() => setShowHistory(true)}>
                                <Clock3 className="size-4" />
                                Previous Exports
                            </Button>
                            <Button onClick={() => void startExport()} disabled={isStarting || Boolean(running) || hasActiveImport}>
                                {isStarting || running ? <Loader2 className="size-4 animate-spin" /> : <Download className="size-4" />}
                                {isStarting ? 'Starting...' : running ? 'Exporting...' : 'Export'}
                            </Button>
                        </>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
