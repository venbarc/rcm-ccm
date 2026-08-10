import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import type { ManagedUser, UserPage } from '@/components/users/types';
import type { AccountTypeOption } from '@/types';
import { Pencil } from 'lucide-react';

interface UserManagementTableProps {
    accountTypes: AccountTypeOption[];
    currentUserId: number;
    isLoading: boolean;
    onEdit: (user: ManagedUser) => void;
    users: UserPage;
}

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

const role = (user: ManagedUser) => (user.is_admin ? 'Administrator' : 'User');

export function UserManagementTable({ accountTypes, currentUserId, isLoading, onEdit, users }: UserManagementTableProps) {
    return (
        <Card className="border-border overflow-hidden">
            <DataLoadingOverlay isLoading={isLoading} label="Loading users...">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[900px] text-sm">
                        <thead className="bg-primary text-primary-foreground text-left text-xs tracking-wide uppercase">
                            <tr>
                                <th className="px-5 py-3">User</th>
                                <th className="px-5 py-3">Role</th>
                                <th className="px-5 py-3">Members under you</th>
                                <th className="px-5 py-3">Accounts</th>
                                <th className="px-5 py-3">Joined</th>
                                <th className="w-20 px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {users.data.map((user) => {
                                const teamOwner = user.admins?.[0];

                                return (
                                    <tr className="hover:bg-secondary/60 bg-white" key={user.id}>
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <span className="bg-secondary text-secondary-foreground flex size-10 shrink-0 items-center justify-center rounded-full font-semibold">
                                                    {initials(user.name)}
                                                </span>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-semibold text-slate-950">{user.name}</p>
                                                        {user.id === currentUserId && <Badge variant="outline">You</Badge>}
                                                    </div>
                                                    <p className="text-muted-foreground">{user.email}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge
                                                className={
                                                    user.is_admin
                                                        ? 'border-border bg-secondary text-secondary-foreground'
                                                        : 'border-slate-200 bg-slate-50 text-slate-700'
                                                }
                                                variant="outline"
                                            >
                                                {role(user)}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4">
                                            {user.is_admin ? (
                                                <span className="font-medium text-slate-800">
                                                    {user.members_under_you_count} {user.members_under_you_count === 1 ? 'member' : 'members'}
                                                </span>
                                            ) : teamOwner ? (
                                                teamOwner.id === currentUserId ? (
                                                    <Badge className="border-border bg-secondary text-secondary-foreground" variant="outline">
                                                        Yes
                                                    </Badge>
                                                ) : (
                                                    <div>
                                                        <Badge className="border-amber-200 bg-amber-50 text-amber-900" variant="outline">
                                                            No
                                                        </Badge>
                                                        <p className="mt-1 text-xs text-amber-700">Under admin {teamOwner.name}</p>
                                                    </div>
                                                )
                                            ) : (
                                                <div>
                                                    <Badge variant="outline">No</Badge>
                                                    <p className="text-muted-foreground mt-1 text-xs">Unassigned</p>
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex flex-wrap gap-1.5">
                                                {(user.account_types ?? []).map((accountValue) => (
                                                    <Badge key={accountValue} variant="secondary">
                                                        {accountTypes.find((account) => account.value === accountValue)?.label ?? accountValue}
                                                    </Badge>
                                                ))}
                                                {(user.account_types ?? []).length === 0 && (
                                                    <span className="text-muted-foreground">No account access</span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="text-muted-foreground px-5 py-4">{new Date(user.created_at).toLocaleDateString()}</td>
                                        <td className="px-5 py-4">
                                            {user.can_manage && (
                                                <Button aria-label={`Edit ${user.name}`} onClick={() => onEdit(user)} size="icon" variant="ghost">
                                                    <Pencil className="size-4" />
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                            {users.data.length === 0 && (
                                <tr>
                                    <td className="text-muted-foreground px-5 py-14 text-center" colSpan={6}>
                                        No users match these filters.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="border-border flex flex-wrap items-center justify-between gap-3 border-t p-4">
                    <p className="text-muted-foreground text-sm">
                        Showing {users.from ?? 0}-{users.to ?? 0} of {users.total}
                    </p>
                    <Pagination links={users.links} />
                </div>
            </DataLoadingOverlay>
        </Card>
    );
}
