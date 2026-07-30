import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import InputError from '@/components/input-error';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { TeamCandidate, TeamUser } from '@/components/users/types';
import { useForm } from '@inertiajs/react';
import { UsersRound, X } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';

interface CandidatePage {
    data: TeamCandidate[];
    current_page: number;
    last_page: number;
    total: number;
}

interface ManageTeamDialogProps {
    admin: TeamUser;
    initialMembers: TeamUser[];
    onClose: () => void;
}

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

export function ManageTeamDialog({ admin, initialMembers, onClose }: ManageTeamDialogProps) {
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
    const save = () => form.patch(`/user-management/${admin.id}/members`, { preserveScroll: true, onSuccess: onClose });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit administrator team</DialogTitle>
                    <DialogDescription>
                        Select multiple users for your active-account team. Users assigned to another administrator remain visible but cannot be
                        selected.
                    </DialogDescription>
                </DialogHeader>
                <div className="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                    <p className="font-semibold text-slate-900">{admin.name}</p>
                    <p className="text-sm text-slate-600">{admin.email}</p>
                </div>
                <div className="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="font-semibold text-slate-950">Members under you</p>
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
                                    {member.name}
                                    <X className="size-3.5 text-blue-500" />
                                </button>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted-foreground mt-3 text-sm">No members selected yet.</p>
                    )}
                </div>
                <div className="space-y-3">
                    <SearchInput
                        onChange={(event) => {
                            setSearch(event.target.value);
                            setPage(1);
                        }}
                        placeholder="Search users by name or email"
                        value={search}
                    />
                    <DataLoadingOverlay className="min-h-48 overflow-hidden rounded-xl border" isLoading={loading} label="Loading users...">
                        <div className="border-b bg-slate-50 px-4 py-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            Non-administrator users
                        </div>
                        {candidates.data.length > 0 ? (
                            <div className="divide-y">
                                {candidates.data.map((member) => {
                                    const isSelected = selected.some((selectedMember) => selectedMember.id === member.id);
                                    const ownedByAnotherAdmin = member.owner !== null && member.owner.id !== admin.id;

                                    return (
                                        <label
                                            className={`flex w-full items-center gap-3 px-4 py-3 text-left ${
                                                member.is_selectable ? 'cursor-pointer hover:bg-blue-50/60' : 'cursor-not-allowed bg-slate-50/70'
                                            }`}
                                            key={member.id}
                                        >
                                            <input
                                                aria-label={`Select ${member.name}`}
                                                checked={isSelected}
                                                className="size-4 shrink-0 accent-blue-700"
                                                disabled={!member.is_selectable}
                                                onChange={() => toggleMember(member)}
                                                type="checkbox"
                                            />
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-900">
                                                {initials(member.name)}
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <strong className="block truncate text-sm text-slate-950">{member.name}</strong>
                                                <span className="text-muted-foreground block truncate text-xs">{member.email}</span>
                                                {ownedByAnotherAdmin && (
                                                    <span className="mt-1 block text-xs font-medium text-amber-700">
                                                        This user is under admin {member.owner?.name}.
                                                    </span>
                                                )}
                                                {member.owner?.id === admin.id && (
                                                    <span className="mt-1 block text-xs font-medium text-blue-700">
                                                        Currently a member under you.
                                                    </span>
                                                )}
                                            </span>
                                            <span className={`text-xs font-semibold ${ownedByAnotherAdmin ? 'text-amber-700' : 'text-blue-700'}`}>
                                                {ownedByAnotherAdmin ? 'Unavailable' : isSelected ? 'Selected' : 'Available'}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-muted-foreground p-8 text-center text-sm">No users match this search.</p>
                        )}
                    </DataLoadingOverlay>
                    <div className="flex items-center justify-between">
                        <p className="text-muted-foreground text-xs">{candidates.total} non-administrator users</p>
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
                        {form.processing ? 'Saving...' : 'Save members'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
