import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { CircleDollarSign, FileText, Upload, UserRoundX } from 'lucide-react';

interface Summary { total: number; unassigned: number; open_balance: string; worked_today: number }
interface ImportRow { id: number; file_name: string; status: string; created_count: number; updated_count: number; created_at: string; importer?: { name: string } }

export default function Dashboard({ summary, recentImports }: { summary: Summary; recentImports: ImportRow[] }) {
    const metrics = [
        { label: 'Total claims', value: summary.total.toLocaleString(), icon: FileText },
        { label: 'Unassigned', value: summary.unassigned.toLocaleString(), icon: UserRoundX },
        { label: 'Open balance', value: `$${Number(summary.open_balance).toLocaleString(undefined, { minimumFractionDigits: 2 })}`, icon: CircleDollarSign },
        { label: 'Worked today', value: summary.worked_today.toLocaleString(), icon: Upload },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            <Head title="Tricity Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <Badge variant="outline">Tricity Pain Associates</Badge>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight">Claims command center</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Monitor inventory, assignments, and the latest claim imports.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map(({ label, value, icon: Icon }) => (
                        <Card key={label}><CardContent className="flex items-center justify-between p-5"><div><p className="text-sm text-muted-foreground">{label}</p><p className="mt-2 text-2xl font-semibold">{value}</p></div><Icon className="size-5 text-primary" /></CardContent></Card>
                    ))}
                </div>
                <Card>
                    <CardHeader className="flex-row items-center justify-between"><CardTitle className="text-lg">Recent imports</CardTitle><Link className="text-sm font-medium text-primary hover:underline" href="/claims-import">View history</Link></CardHeader>
                    <CardContent>
                        <div className="divide-y">
                            {recentImports.length === 0 ? <p className="py-8 text-center text-sm text-muted-foreground">No claims imported yet.</p> : recentImports.map((item) => (
                                <div className="flex flex-wrap items-center justify-between gap-3 py-3" key={item.id}><div><p className="font-medium">{item.file_name}</p><p className="text-xs text-muted-foreground">{item.importer?.name ?? 'Unknown'} · {new Date(item.created_at).toLocaleString()}</p></div><div className="flex items-center gap-3 text-sm"><span>{item.created_count} new</span><span>{item.updated_count} updated</span><Badge variant={item.status === 'completed' ? 'secondary' : 'destructive'}>{item.status}</Badge></div></div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
