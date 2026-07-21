import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { ManagedUser, TeamUser, UserPage } from '@/components/users/types';
import { UserManagementTable } from '@/components/users/user-management-table';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import { type AccountTypeOption, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Check, Search, ShieldCheck, UserRoundCog, UsersRound } from 'lucide-react';
import { FormEvent, useDeferredValue, useEffect, useState } from 'react';

interface CandidatePage {
    data: TeamUser[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Filters {
    search: string;
    role: string;
}

interface Summary {
    total: number;
    admins: number;
    claimsManagers: number;
    myTeam: number;
}

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
function EditAccessDialog({ user, accountTypes, onClose }: { user: ManagedUser; accountTypes: AccountTypeOption[]; onClose: () => void }) {
    const form = useForm({
        is_admin: user.is_admin,
        can_assign_claims: user.can_assign_claims,
        account_types: user.account_types ?? [],
    });
    const toggleAccount = (value: string) =>
        form.setData(
            'account_types',
            form.data.account_types.includes(value) ? form.data.account_types.filter((item) => item !== value) : [...form.data.account_types, value],
        );
    const save = () => form.patch(`/user-management/${user.id}`, { preserveScroll: true, onSuccess: onClose });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit user access</DialogTitle>
                    <DialogDescription>
                        Permissions apply to {user.name}. OneAccess remains the identity provider and no local approval is required.
                    </DialogDescription>
                </DialogHeader>
                <div className="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                    <p className="font-semibold text-slate-900">{user.name}</p>
                    <p className="text-sm text-slate-600">{user.email}</p>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm">
                        <input
                            checked={form.data.is_admin}
                            className="mt-1 accent-blue-700"
                            onChange={(event) => form.setData('is_admin', event.target.checked)}
                            type="checkbox"
                        />
                        <span>
                            <strong className="block text-slate-900">Administrator</strong>
                            <span className="text-muted-foreground text-xs">Manages access and their own team</span>
                        </span>
                    </label>
                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm">
                        <input
                            checked={form.data.can_assign_claims}
                            className="mt-1 accent-blue-700"
                            onChange={(event) => form.setData('can_assign_claims', event.target.checked)}
                            type="checkbox"
                        />
                        <span>
                            <strong className="block text-slate-900">Claims manager</strong>
                            <span className="text-muted-foreground text-xs">Can assign claims within their scope</span>
                        </span>
                    </label>
                </div>
                <div>
                    <p className="mb-2 text-sm font-semibold text-slate-900">Account access</p>
                    <div className="grid gap-2 sm:grid-cols-2">
                        {accountTypes.map((account) => (
                            <label
                                className={`flex items-center gap-3 rounded-xl border p-3 text-sm ${account.ready ? 'cursor-pointer bg-white' : 'text-muted-foreground cursor-not-allowed bg-slate-50'}`}
                                key={account.value}
                            >
                                <input
                                    checked={form.data.account_types.includes(account.value)}
                                    className="accent-blue-700"
                                    disabled={!account.ready}
                                    onChange={() => toggleAccount(account.value)}
                                    type="checkbox"
                                />
                                <span>{account.label}</span>
                                {!account.ready && (
                                    <Badge className="ml-auto" variant="outline">
                                        Coming soon
                                    </Badge>
                                )}
                            </label>
                        ))}
                    </div>
                    <InputError message={form.errors.account_types} />
                </div>
                <DialogFooter>
                    <Button onClick={onClose} variant="outline">
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={save}>
                        <ShieldCheck />
                        {form.processing ? 'Saving...' : 'Save access'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ManageTeamDialog({ adminId, initialMembers, onClose }: { adminId: number; initialMembers: TeamUser[]; onClose: () => void }) {
    const form = useForm({ member_ids: initialMembers.map((member) => member.id) });
    const [selected, setSelected] = useState<TeamUser[]>(initialMembers);
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(search);
    const [page, setPage] = useState(1);
    const [candidates, setCandidates] = useState<CandidatePage>({ data: [], current_page: 1, last_page: 1, total: 0 });
    const requestKey = `${deferredSearch}:${page}`;
    const [loadedRequestKey, setLoadedRequestKey] = useState('');
    const loading = requestKey !== loadedRequestKey;

    useEffect(() => {
        const controller = new AbortController();
        const params = new URLSearchParams({ search: deferredSearch, page: String(page), per_page: '10' });
        fetch(`/user-management/available-members?${params}`, { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then((response) => {
                if (!response.ok) throw new Error('Unable to load team members.');
                return response.json() as Promise<CandidatePage>;
            })
            .then(setCandidates)
            .catch((error: Error) => {
                if (error.name !== 'AbortError') setCandidates({ data: [], current_page: 1, last_page: 1, total: 0 });
            })
            .finally(() => setLoadedRequestKey(`${deferredSearch}:${page}`));

        return () => controller.abort();
    }, [deferredSearch, page]);

    const toggleMember = (member: TeamUser) => {
        const next = selected.some((item) => item.id === member.id) ? selected.filter((item) => item.id !== member.id) : [...selected, member];
        setSelected(next);
        form.setData(
            'member_ids',
            next.map((item) => item.id),
        );
    };
    const save = () => form.patch(`/user-management/${adminId}/members`, { preserveScroll: true, onSuccess: onClose });
    const available = candidates.data.filter((candidate) => !selected.some((member) => member.id === candidate.id));

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Manage my team</DialogTitle>
                    <DialogDescription>
                        Select Tricity users who should receive claims from you. Users assigned to another administrator are protected and will not
                        appear here.
                    </DialogDescription>
                </DialogHeader>
                <div className="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="font-semibold text-slate-950">Current team</p>
                            <p className="text-muted-foreground text-sm">
                                {selected.length} {selected.length === 1 ? 'member' : 'members'} selected
                            </p>
                        </div>
                        <UsersRound className="size-5 text-blue-800" />
                    </div>
                    {selected.length > 0 ? (
                        <div className="mt-3 flex flex-wrap gap-2">
                            {selected.map((member) => (
                                <button
                                    className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white px-3 py-1.5 text-sm text-blue-950 hover:bg-blue-50"
                                    key={member.id}
                                    onClick={() => toggleMember(member)}
                                    type="button"
                                >
                                    <Check className="size-3.5" />
                                    {member.name}
                                    <span className="text-blue-500">x</span>
                                </button>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted-foreground mt-3 text-sm">No members selected yet.</p>
                    )}
                </div>
                <div className="space-y-3">
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-3 left-3 size-4" />
                        <Input
                            className="pl-9"
                            onChange={(event) => {
                                setSearch(event.target.value);
                                setPage(1);
                            }}
                            placeholder="Search available users"
                            value={search}
                        />
                    </div>
                    <DataLoadingOverlay className="min-h-48 overflow-hidden rounded-xl border" isLoading={loading} label="Loading users...">
                        <div className="border-b bg-slate-50 px-4 py-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            Available for your team
                        </div>
                        {available.length > 0 ? (
                            <div className="divide-y">
                                {available.map((member) => (
                                    <button
                                        className="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-blue-50/60"
                                        key={member.id}
                                        onClick={() => toggleMember(member)}
                                        type="button"
                                    >
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-900">
                                            {initials(member.name)}
                                        </span>
                                        <span className="min-w-0">
                                            <strong className="block truncate text-sm text-slate-950">{member.name}</strong>
                                            <span className="text-muted-foreground block truncate text-xs">{member.email}</span>
                                        </span>
                                        <span className="ml-auto text-xs font-semibold text-blue-700">Add</span>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground p-8 text-center text-sm">No available users match this search.</p>
                        )}
                    </DataLoadingOverlay>
                    <div className="flex items-center justify-between">
                        <p className="text-muted-foreground text-xs">{candidates.total} eligible users</p>
                        <div className="flex gap-2">
                            <Button
                                disabled={page <= 1 || loading}
                                onClick={() => setPage((value) => value - 1)}
                                size="sm"
                                type="button"
                                variant="outline"
                            >
                                Previous
                            </Button>
                            <Button
                                disabled={page >= candidates.last_page || loading}
                                onClick={() => setPage((value) => value + 1)}
                                size="sm"
                                type="button"
                                variant="outline"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                    <InputError message={form.errors.member_ids} />
                </div>
                <DialogFooter>
                    <Button onClick={onClose} variant="outline">
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={save}>
                        <UsersRound />
                        {form.processing ? 'Saving...' : 'Save team'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function UsersIndex({
    users,
    accountTypes,
    filters,
    summary,
    myTeamMembers,
}: {
    users: UserPage;
    accountTypes: AccountTypeOption[];
    filters: Filters;
    summary: Summary;
    myTeamMembers: TeamUser[];
}) {
    const { auth } = usePage<SharedData>().props;
    const isPageLoading = useInertiaLoading();
    const [editing, setEditing] = useState<ManagedUser | null>(null);
    const [managingTeam, setManagingTeam] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');
    const [roleFilter, setRoleFilter] = useState(filters.role ?? '');
    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get('/user-management', { search: search || undefined, role: roleFilter || undefined }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'User Management', href: '/user-management' }]}>
            <Head title="User Management" />
            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="mb-1 text-xs font-semibold tracking-[0.2em] text-blue-700 uppercase">Administration</p>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-950">User Management</h1>
                        <p className="text-muted-foreground text-sm">
                            Control Tricity access, review unassigned users, and choose who belongs to your active-account team.
                        </p>
                    </div>
                    <Button onClick={() => setManagingTeam(true)}>
                        <UserRoundCog />
                        Manage my team
                    </Button>
                </div>

                <Card className="border-blue-100 bg-blue-50/60">
                    <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4 text-sm text-blue-950">
                        <div>
                            <strong>OneAccess is the approval source.</strong>
                            <span className="ml-1 text-blue-900/80">
                                Users appear after first sign-in, and you can place eligible unassigned users under your team here.
                            </span>
                        </div>
                        <Badge className="border-blue-200 bg-white text-blue-900" variant="outline">
                            No local approval queue
                        </Badge>
                    </CardContent>
                </Card>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Total users', value: summary.total, icon: UsersRound },
                        { label: 'My team', value: summary.myTeam, icon: UserRoundCog },
                        { label: 'Claims managers', value: summary.claimsManagers, icon: Check },
                        { label: 'Administrators', value: summary.admins, icon: ShieldCheck },
                    ].map(({ label, value, icon: Icon }, index) => (
                        <Card className={index === 0 ? 'border-l-4 border-l-blue-800' : 'border-blue-100'} key={label}>
                            <CardContent className="flex items-center justify-between p-4">
                                <div>
                                    <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">{label}</p>
                                    <p className="mt-1 text-2xl font-semibold text-slate-950">{value}</p>
                                </div>
                                <div className="rounded-xl bg-blue-50 p-2.5 text-blue-800">
                                    <Icon className="size-5" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="border-blue-100">
                    <CardContent className="p-4">
                        <form className="grid gap-3 md:grid-cols-[minmax(240px,1fr)_200px_auto_auto]" onSubmit={applyFilters}>
                            <div className="relative">
                                <Search className="text-muted-foreground absolute top-3 left-3 size-4" />
                                <Input
                                    className="pl-9"
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search name or email"
                                    value={search}
                                />
                            </div>
                            <select
                                className="h-10 rounded-md border bg-white px-3 text-sm"
                                onChange={(event) => setRoleFilter(event.target.value)}
                                value={roleFilter}
                            >
                                <option value="">All roles</option>
                                <option value="admin">Administrators</option>
                                <option value="assigner">Claims managers</option>
                                <option value="user">Claims users</option>
                            </select>
                            <Button type="submit">Apply filters</Button>
                            <Button onClick={() => router.get('/user-management')} type="button" variant="ghost">
                                Clear
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <UserManagementTable
                    accountTypes={accountTypes}
                    currentUserId={auth.user.id}
                    isLoading={isPageLoading}
                    onEdit={setEditing}
                    users={users}
                />
            </div>
            {editing && <EditAccessDialog accountTypes={accountTypes} key={editing.id} onClose={() => setEditing(null)} user={editing} />}
            {managingTeam && <ManageTeamDialog adminId={auth.user.id} initialMembers={myTeamMembers} onClose={() => setManagingTeam(false)} />}
        </AppLayout>
    );
}
