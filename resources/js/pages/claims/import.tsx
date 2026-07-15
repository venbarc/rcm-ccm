import InputError from '@/components/input-error';
import { Pagination, type PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FileSpreadsheet, Upload } from 'lucide-react';
import { FormEvent } from 'react';

interface ImportRow { id: number; file_name: string; status: string; created_count: number; updated_count: number; skipped_count: number; error_message: string | null; created_at: string; importer: { name: string } }
interface ImportPage { data: ImportRow[]; links: PaginationLink[] }

export default function ClaimImport({ imports }: { imports: ImportPage }) {
    const form = useForm<{ file: File | null }>({ file: null });
    const submit = (event: FormEvent) => { event.preventDefault(); form.post('/claims-import', { forceFormData: true, onSuccess: () => form.reset() }); };

    return <AppLayout breadcrumbs={[{ title: 'Claims', href: '/claims' }, { title: 'Import', href: '/claims-import' }]}><Head title="Import Claims" /><div className="flex flex-1 flex-col gap-6 p-4 md:p-6"><div><h1 className="text-3xl font-semibold tracking-tight">Import claims</h1><p className="text-sm text-muted-foreground">Upload a CSV or Excel workbook. Claim ID and Patient Name are required.</p></div><Card><CardHeader><CardTitle className="flex items-center gap-2 text-lg"><FileSpreadsheet className="size-5" /> Tricity claim file</CardTitle><CardDescription>Recognized columns include Claim ID, Patient Name, DOS, Payer, Provider, CPT, Billed Amount, Balance, and Status.</CardDescription></CardHeader><CardContent><form className="flex flex-col gap-4 sm:flex-row sm:items-start" onSubmit={submit}><div className="flex-1"><input accept=".csv,.txt,.xlsx,.xls" className="block w-full rounded-md border bg-background p-2 text-sm" onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)} type="file" /><InputError message={form.errors.file} /></div><Button disabled={!form.data.file || form.processing} type="submit"><Upload /> {form.processing ? 'Importing…' : 'Import file'}</Button></form></CardContent></Card><Card><CardHeader><CardTitle className="text-lg">Import history</CardTitle></CardHeader><CardContent><div className="divide-y">{imports.data.length === 0 ? <p className="py-8 text-center text-sm text-muted-foreground">No import history.</p> : imports.data.map((item) => <div className="flex flex-wrap items-center justify-between gap-4 py-4" key={item.id}><div><p className="font-medium">{item.file_name}</p><p className="text-xs text-muted-foreground">{item.importer.name} · {new Date(item.created_at).toLocaleString()}</p>{item.error_message && <p className="mt-1 text-xs text-destructive">{item.error_message}</p>}</div><div className="flex flex-wrap items-center gap-3 text-sm"><span>{item.created_count} created</span><span>{item.updated_count} updated</span><span>{item.skipped_count} skipped</span><Badge variant={item.status === 'completed' ? 'secondary' : item.status === 'failed' ? 'destructive' : 'outline'}>{item.status}</Badge></div></div>)}</div><div className="mt-4"><Pagination links={imports.links} /></div></CardContent></Card></div></AppLayout>;
}
