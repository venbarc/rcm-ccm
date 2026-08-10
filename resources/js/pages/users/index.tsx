import { SearchInput } from '@/components/search-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { EditAccessDialog } from '@/components/users/edit-access-dialog';
import { ManageTeamDialog } from '@/components/users/manage-team-dialog';
import type { ManagedUser, TeamUser, UserPage } from '@/components/users/types';
import { UserManagementTable } from '@/components/users/user-management-table';
import { useActiveAccount } from '@/hooks/use-active-account';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/layouts/app-layout';
import type { AccountTypeOption, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ShieldCheck, UserRound, UserRoundCog, UsersRound } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface Filters {
    search: string;
    role: string;
    team: string;
}

interface Summary {
    total: number;
    admins: number;
    users: number;
    myTeam: number;
}

interface UsersIndexProps {
    users: UserPage;
    accountTypes: AccountTypeOption[];
    filters: Filters;
    summary: Summary;
    myTeamMembers: TeamUser[];
}

export default function UsersIndex({ users, accountTypes, filters, summary, myTeamMembers }: UsersIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const { label: accountLabel } = useActiveAccount();
    const isPageLoading = useInertiaLoading();
    const [editing, setEditing] = useState<ManagedUser | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [roleFilter, setRoleFilter] = useState(filters.role ?? '');
    const [teamFilter, setTeamFilter] = useState(filters.team ?? '');
    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/user-management',
            { search: search || undefined, role: roleFilter || undefined, team: teamFilter || undefined },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'User Management', href: '/user-management' }]}>
            <Head title="User Management" />
            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-primary mb-1 text-xs font-semibold tracking-[0.2em] uppercase">Administration</p>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-950">User Management</h1>
                        <p className="text-muted-foreground text-sm">
                            Control {accountLabel} access, review unassigned users, and choose who belongs to your active-account team.
                        </p>
                    </div>
                </div>

                <Card className="border-border bg-secondary/60">
                    <CardContent className="text-secondary-foreground flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                        <div>
                            <strong>OneAccess is the approval source.</strong>
                            <span className="text-secondary-foreground/80 ml-1">
                                All CCM users remain visible. Enable account access before adding a user to that account's team.
                            </span>
                        </div>
                        <Badge className="border-border text-primary bg-white" variant="outline">
                            No local approval queue
                        </Badge>
                    </CardContent>
                </Card>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Total users', value: summary.total, icon: UsersRound },
                        { label: 'My team', value: summary.myTeam, icon: UserRoundCog },
                        { label: 'Users', value: summary.users, icon: UserRound },
                        { label: 'Administrators', value: summary.admins, icon: ShieldCheck },
                    ].map(({ label, value, icon: Icon }, index) => (
                        <Card className={index === 0 ? 'border-l-primary border-l-4' : 'border-border'} key={label}>
                            <CardContent className="flex items-center justify-between p-4">
                                <div>
                                    <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">{label}</p>
                                    <p className="mt-1 text-2xl font-semibold text-slate-950">{value}</p>
                                </div>
                                <div className="bg-secondary text-primary rounded-xl p-2.5">
                                    <Icon className="size-5" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="border-border">
                    <CardContent className="p-4">
                        <form className="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_200px_220px_auto_auto]" onSubmit={applyFilters}>
                            <SearchInput onChange={(event) => setSearch(event.target.value)} placeholder="Search name or email" value={search} />
                            <select
                                className="h-10 rounded-md border bg-white px-3 text-sm"
                                onChange={(event) => setRoleFilter(event.target.value)}
                                value={roleFilter}
                            >
                                <option value="">All roles</option>
                                <option value="admin">Administrators</option>
                                <option value="user">Users</option>
                            </select>
                            <select
                                className="h-10 rounded-md border bg-white px-3 text-sm"
                                onChange={(event) => setTeamFilter(event.target.value)}
                                value={teamFilter}
                            >
                                <option value="">All membership statuses</option>
                                <option value="mine">Members under you</option>
                                <option value="unassigned">Unassigned members</option>
                                <option value="other">Under another admin</option>
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
            {editing?.id === auth.user.id && editing.is_admin ? (
                <ManageTeamDialog admin={editing} initialMembers={myTeamMembers} key={editing.id} onClose={() => setEditing(null)} />
            ) : (
                editing && <EditAccessDialog accountTypes={accountTypes} key={editing.id} onClose={() => setEditing(null)} user={editing} />
            )}
        </AppLayout>
    );
}
