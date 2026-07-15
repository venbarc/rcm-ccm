import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { Clock3, LogOut } from 'lucide-react';

export default function PendingApproval() {
    return <main className="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_left,hsl(var(--primary)/0.12),transparent_38%),linear-gradient(to_bottom_right,hsl(var(--background)),hsl(var(--muted)))] p-6"><Head title="Approval pending" /><Card className="w-full max-w-lg"><CardHeader className="text-center"><div className="mx-auto mb-3 flex size-14 items-center justify-center rounded-full bg-amber-100 text-amber-700"><Clock3 className="size-7" /></div><CardTitle>Access request pending</CardTitle><CardDescription>Your One Access identity is connected. A Tricity administrator must approve your local claims access before you can continue.</CardDescription></CardHeader><CardContent><Button className="w-full" variant="outline" asChild><Link as="button" href={route('logout')} method="post"><LogOut /> Return to One Access</Link></Button></CardContent></Card></main>;
}
