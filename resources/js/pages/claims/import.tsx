import InputError from '@/components/input-error';
import { Pagination, type PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle2, ChevronDown, ChevronUp, FileSpreadsheet, History, Loader2, Upload } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface ImportRow {
    id: number;
    file_name: string;
    status: string;
    total_rows: number;
    processed_rows: number;
    created_count: number;
    updated_count: number;
    skipped_count: number;
    failed_count: number;
    error_message: string | null;
    created_at: string;
    importer: { name: string };
}
interface ImportPage {
    data: ImportRow[];
    links: PaginationLink[];
}
interface Progress {
    id: number;
    status: string;
    total_rows: number;
    processed_rows: number;
    created_count: number;
    updated_count: number;
    skipped_count: number;
    failed_count: number;
    error_message: string | null;
    progress_percentage: number;
}

const expectedColumns = [
    'CPT',
    'Location',
    'Bill ID',
    'Invoice Rate Per Unit',
    'CF Invoice Amount',
    'Payments',
    'True Balance',
    'True Charge',
    'Units',
    'BillingID-CPT',
    'Charges',
    'ModMed_Claim_Status',
    'CF Invoice Date',
    'Patient DOB',
    'Patient First Name',
    'Patient Last Name',
    'Patient Name',
    'Patient MRN',
    'Payer',
    'Payer-CPT',
    'Place of Service Code',
    'Posted Date Month/Year',
    'Primary Provider',
    'Service Date',
    'True Charge Per Unit',
];

export default function ClaimImport({ imports, activeImportId }: { imports: ImportPage; activeImportId: number | null }) {
    const form = useForm<{ file: File | null }>({ file: null });
    const [progress, setProgress] = useState<Progress | null>(null);
    const [showHistory, setShowHistory] = useState(false);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/claims-import', { forceFormData: true, onSuccess: () => form.reset() });
    };

    useEffect(() => {
        if (!activeImportId) return;
        let cancelled = false;
        const poll = async () => {
            try {
                const response = await fetch(`/claims-import/${activeImportId}/progress`, { headers: { Accept: 'application/json' } });
                if (!response.ok || cancelled) return;
                const next = (await response.json()) as Progress;
                setProgress(next);
                if (next.status === 'processing' || next.status === 'queued') {
                    window.setTimeout(poll, 1500);
                } else {
                    router.reload({ only: ['imports', 'activeImportId'] });
                }
            } catch {
                if (!cancelled) window.setTimeout(poll, 3000);
            }
        };
        void poll();
        return () => {
            cancelled = true;
        };
    }, [activeImportId]);

    const active = progress ?? imports.data.find((item) => item.id === activeImportId) ?? null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Claims', href: '/claims' },
                { title: 'Import', href: '/claims-import' },
            ]}
        >
            <Head title="Import Tricity Claims" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-muted-foreground mb-1 text-xs font-semibold tracking-[0.2em] uppercase">Queue-backed ingestion</p>
                        <h1 className="text-3xl font-semibold tracking-tight">Import Tricity claims</h1>
                        <p className="text-muted-foreground text-sm">Large files are processed in memory-safe chunks by the queue worker.</p>
                    </div>
                    <Button
                        aria-controls="import-history"
                        aria-expanded={showHistory}
                        className="gap-2 self-start"
                        onClick={() => setShowHistory((visible) => !visible)}
                        variant="outline"
                    >
                        <History className="size-4" /> Import History{' '}
                        {showHistory ? <ChevronUp className="size-4" /> : <ChevronDown className="size-4" />}
                    </Button>
                </div>
                {active && (active.status === 'processing' || active.status === 'queued') && (
                    <Card className="border-sky-200 bg-sky-50/40">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <Loader2 className="size-5 animate-spin text-sky-600" />
                                    <div>
                                        <p className="font-medium">Import in progress</p>
                                        <p className="text-muted-foreground text-sm">
                                            {active.processed_rows.toLocaleString()} of {active.total_rows.toLocaleString()} rows processed
                                        </p>
                                    </div>
                                </div>
                                <span className="text-lg font-semibold tabular-nums">
                                    {'progress_percentage' in active
                                        ? active.progress_percentage
                                        : Math.round((active.processed_rows / Math.max(1, active.total_rows)) * 100)}
                                    %
                                </span>
                            </div>
                            <div className="mt-4 h-2 overflow-hidden rounded-full bg-sky-100">
                                <div
                                    className="h-full rounded-full bg-sky-600 transition-all"
                                    style={{ width: `${'progress_percentage' in active ? active.progress_percentage : 0}%` }}
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}
                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <FileSpreadsheet className="size-5" /> Tricity billing workbook
                            </CardTitle>
                            <CardDescription>
                                Upload CSV, XLSX, or XLS up to 20 MB. Bill ID is required and groups its CPT rows into one claim. Every workbook
                                column is retained in the raw payload; displayed fields are also normalized for fast filtering and sorting. Reimports
                                refresh source data while preserving assignments and manually worked fields.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={submit}>
                                <input
                                    accept=".csv,.txt,.xlsx,.xls"
                                    className="bg-background block w-full rounded-md border p-3 text-sm"
                                    onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)}
                                    type="file"
                                />
                                <InputError message={form.errors.file} />
                                <Button disabled={!form.data.file || form.processing || Boolean(activeImportId)} type="submit">
                                    <Upload /> {form.processing ? 'Uploading...' : activeImportId ? 'Import in progress' : 'Queue import'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Recognized Tricity columns</CardTitle>
                            <CardDescription>Other columns are retained in the raw source payload.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-2">
                            {expectedColumns.map((column) => (
                                <Badge key={column} variant="outline">
                                    {column}
                                </Badge>
                            ))}
                        </CardContent>
                    </Card>
                </div>
                {showHistory && (
                    <Card id="import-history">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <History className="size-5" /> Import History
                            </CardTitle>
                            <CardDescription>View the history of all Tricity claim imports.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="divide-y">
                                {imports.data.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-12 text-center">
                                        <History className="text-muted-foreground/50 mb-4 size-12" />
                                        <h3 className="text-muted-foreground mb-2 text-lg font-medium">No Import History</h3>
                                        <p className="text-muted-foreground text-sm">
                                            Your import history will appear here after you upload and process files.
                                        </p>
                                    </div>
                                ) : (
                                    imports.data.map((item) => {
                                        const percent =
                                            item.total_rows > 0 ? Math.min(100, Math.round((item.processed_rows / item.total_rows) * 100)) : 0;
                                        return (
                                            <div
                                                className="grid gap-4 py-4 lg:grid-cols-[minmax(220px,1fr)_minmax(320px,1fr)_auto] lg:items-center"
                                                key={item.id}
                                            >
                                                <div>
                                                    <p className="font-medium">{item.file_name}</p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {item.importer.name} · {new Date(item.created_at).toLocaleString()}
                                                    </p>
                                                    {item.error_message && <p className="text-destructive mt-1 text-xs">{item.error_message}</p>}
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground mb-1 flex justify-between text-xs">
                                                        <span>
                                                            {item.processed_rows.toLocaleString()} / {item.total_rows.toLocaleString()} rows
                                                        </span>
                                                        <span>{percent}%</span>
                                                    </div>
                                                    <div className="bg-muted h-1.5 overflow-hidden rounded-full">
                                                        <div
                                                            className={`h-full ${item.status === 'failed' ? 'bg-destructive' : 'bg-primary'}`}
                                                            style={{ width: `${percent}%` }}
                                                        />
                                                    </div>
                                                    <div className="mt-2 flex flex-wrap gap-3 text-xs">
                                                        <span>{item.created_count} created</span>
                                                        <span>{item.updated_count} updated</span>
                                                        <span>{item.skipped_count} skipped</span>
                                                    </div>
                                                </div>
                                                <Badge
                                                    variant={
                                                        item.status === 'completed'
                                                            ? 'secondary'
                                                            : item.status === 'failed'
                                                              ? 'destructive'
                                                              : 'outline'
                                                    }
                                                >
                                                    {item.status === 'completed' && <CheckCircle2 className="mr-1 size-3" />}
                                                    {item.status}
                                                </Badge>
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                            {imports.data.length > 0 && (
                                <div className="mt-4">
                                    <Pagination links={imports.links} />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
