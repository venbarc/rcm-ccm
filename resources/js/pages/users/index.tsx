import { Pagination, type PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type AccountTypeOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

interface ManagedUser { id: number; name: string; email: string; is_admin: boolean; is_approved: boolean; can_assign_claims: boolean; account_types: string[] | null; created_at: string }
interface UserPage { data: ManagedUser[]; links: PaginationLink[] }

function UserRow({ user, accountTypes }: { user: ManagedUser; accountTypes: AccountTypeOption[] }) {
    const form = useForm({ is_approved: user.is_approved, is_admin: user.is_admin, can_assign_claims: user.can_assign_claims, account_types: user.account_types ?? [] });
    const toggleAccount = (value: string) => form.setData('account_types', form.data.account_types.includes(value) ? form.data.account_types.filter((item) => item !== value) : [...form.data.account_types, value]);
    return <div className="grid gap-4 p-5 xl:grid-cols-[1fr_180px_220px_auto] xl:items-center"><div><div className="flex flex-wrap items-center gap-2"><p className="font-medium">{user.name}</p>{!user.is_approved && <Badge variant="destructive">Pending approval</Badge>}{user.is_admin && <Badge>Admin</Badge>}</div><p className="text-sm text-muted-foreground">{user.email}</p><p className="mt-1 text-xs text-muted-foreground">Joined {new Date(user.created_at).toLocaleDateString()}</p></div><div className="space-y-2 text-sm"><label className="flex items-center gap-2"><input checked={form.data.is_approved} onChange={(e) => form.setData('is_approved', e.target.checked)} type="checkbox" /> Approved</label><label className="flex items-center gap-2"><input checked={form.data.is_admin} onChange={(e) => form.setData('is_admin', e.target.checked)} type="checkbox" /> Administrator</label><label className="flex items-center gap-2"><input checked={form.data.can_assign_claims} onChange={(e) => form.setData('can_assign_claims', e.target.checked)} type="checkbox" /> Can assign claims</label></div><div><p className="mb-2 text-xs font-medium uppercase text-muted-foreground">Account access</p><div className="space-y-2">{accountTypes.map((account) => <label className={`flex items-center gap-2 text-sm ${!account.ready ? 'text-muted-foreground' : ''}`} key={account.value}><input checked={form.data.account_types.includes(account.value)} disabled={!account.ready} onChange={() => toggleAccount(account.value)} type="checkbox" /> {account.label}{!account.ready && <span className="text-xs">(pending)</span>}</label>)}</div></div><Button disabled={form.processing} onClick={() => form.patch(`/user-management/${user.id}`, { preserveScroll: true })}><ShieldCheck /> Save access</Button></div>;
}

export default function UsersIndex({ users, accountTypes }: { users: UserPage; accountTypes: AccountTypeOption[] }) {
    return <AppLayout breadcrumbs={[{ title: 'User Management', href: '/user-management' }]}><Head title="User Management" /><div className="flex flex-1 flex-col gap-5 p-4 md:p-6"><div><h1 className="text-3xl font-semibold tracking-tight">User management</h1><p className="text-sm text-muted-foreground">Approve One Access users and control Tricity claims permissions.</p></div><Card><CardContent className="divide-y p-0">{users.data.map((user) => <UserRow accountTypes={accountTypes} key={user.id} user={user} />)}</CardContent><div className="border-t p-4"><Pagination links={users.links} /></div></Card></div></AppLayout>;
}
