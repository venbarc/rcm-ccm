import { Pagination, type PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileSpreadsheet, Search, Users } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface UserOption { id: number; name: string; email: string }
interface Claim { id: number; external_id: string; patient_name: string; date_of_service: string | null; payer: string | null; provider: string | null; cpt_code: string | null; billed_amount: string; balance: string; status: string; priority: string; assigned_to: number | null; assignee: UserOption | null }
interface ClaimPage { data: Claim[]; links: PaginationLink[]; from: number | null; to: number | null; total: number }

export default function ClaimsIndex({ claims, filters, assignees, statuses }: { claims: ClaimPage; filters: Record<string, string>; assignees: UserOption[]; statuses: string[] }) {
    const { auth } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [assignedTo, setAssignedTo] = useState(filters.assigned_to ?? '');

    const filter = (event: FormEvent) => {
        event.preventDefault();
        router.get('/claims', { search: search || undefined, status: status || undefined, assigned_to: assignedTo || undefined }, { preserveState: true, replace: true });
    };

    const update = (claim: Claim, values: Record<string, string | number | null>) => router.patch(`/claims/${claim.id}`, values, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ title: 'Claims', href: '/claims' }]}>
            <Head title="Claims" />
            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4"><div><h1 className="text-3xl font-semibold tracking-tight">Claims</h1><p className="text-sm text-muted-foreground">Tricity claim inventory and work status.</p></div><div className="flex gap-2">{(auth.user.is_admin || auth.user.can_assign_claims) && <Button variant="outline" asChild><Link href="/assignments"><Users /> Assign claims</Link></Button>}{auth.user.is_admin && <Button asChild><Link href="/claims-import"><FileSpreadsheet /> Import</Link></Button>}</div></div>
                <Card><CardContent className="p-4"><form className="grid gap-3 md:grid-cols-[1fr_180px_220px_auto]" onSubmit={filter}><div className="relative"><Search className="absolute left-3 top-3 size-4 text-muted-foreground" /><Input className="pl-9" onChange={(e) => setSearch(e.target.value)} placeholder="Claim, patient, payer, provider" value={search} /></div><select className="h-10 rounded-md border bg-background px-3 text-sm" onChange={(e) => setStatus(e.target.value)} value={status}><option value="">All statuses</option>{statuses.map((item) => <option key={item} value={item}>{item.replaceAll('_', ' ')}</option>)}</select><select className="h-10 rounded-md border bg-background px-3 text-sm" onChange={(e) => setAssignedTo(e.target.value)} value={assignedTo}><option value="">All assignees</option><option value="unassigned">Unassigned</option>{assignees.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}</select><Button type="submit">Apply</Button></form></CardContent></Card>
                <Card className="overflow-hidden"><div className="overflow-x-auto"><table className="w-full min-w-[1100px] text-sm"><thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground"><tr><th className="px-4 py-3">Claim / patient</th><th className="px-4 py-3">DOS</th><th className="px-4 py-3">Payer / provider</th><th className="px-4 py-3">CPT</th><th className="px-4 py-3 text-right">Billed</th><th className="px-4 py-3 text-right">Balance</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Priority</th><th className="px-4 py-3">Assigned to</th></tr></thead><tbody className="divide-y">{claims.data.map((claim) => <tr className="hover:bg-muted/30" key={claim.id}><td className="px-4 py-3"><p className="font-medium">{claim.external_id}</p><p className="text-muted-foreground">{claim.patient_name}</p></td><td className="px-4 py-3">{claim.date_of_service ?? '—'}</td><td className="px-4 py-3"><p>{claim.payer ?? '—'}</p><p className="text-xs text-muted-foreground">{claim.provider ?? '—'}</p></td><td className="px-4 py-3">{claim.cpt_code ?? '—'}</td><td className="px-4 py-3 text-right">${Number(claim.billed_amount).toLocaleString()}</td><td className="px-4 py-3 text-right font-medium">${Number(claim.balance).toLocaleString()}</td><td className="px-4 py-3"><select className="h-8 rounded border bg-background px-2" defaultValue={claim.status} onChange={(e) => update(claim, { status: e.target.value })}>{statuses.map((item) => <option key={item} value={item}>{item.replaceAll('_', ' ')}</option>)}</select></td><td className="px-4 py-3"><select className="h-8 rounded border bg-background px-2" defaultValue={claim.priority} onChange={(e) => update(claim, { priority: e.target.value })}>{['low', 'normal', 'high', 'urgent'].map((item) => <option key={item}>{item}</option>)}</select></td><td className="px-4 py-3">{claim.assignee ? <Badge variant="outline">{claim.assignee.name}</Badge> : <span className="text-muted-foreground">Unassigned</span>}</td></tr>)}</tbody></table>{claims.data.length === 0 && <p className="p-12 text-center text-muted-foreground">No claims match these filters.</p>}</div><div className="flex flex-wrap items-center justify-between gap-3 border-t p-4"><p className="text-sm text-muted-foreground">Showing {claims.from ?? 0}–{claims.to ?? 0} of {claims.total}</p><Pagination links={claims.links} /></div></Card>
            </div>
        </AppLayout>
    );
}
