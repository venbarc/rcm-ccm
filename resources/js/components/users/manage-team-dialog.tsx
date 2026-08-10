import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import InputError from '@/components/input-error';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { TeamCandidate, TeamUser } from '@/components/users/types';
import { useForm } from '@inertiajs/react';
import { UsersRound, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const CANDIDATES_PER_PAGE = 10;
const SEARCH_DEBOUNCE_MS = 300;

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
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [page, setPage] = useState(1);
    const [candidates, setCandidates] = useState<CandidatePage>({ data: [], current_page: 1, last_page: 1, total: 0 });
    const requestKey = `${debouncedSearch}:${page}`;
    const [loadedRequestKey, setLoadedRequestKey] = useState('');
    const loading = search.trim() !== debouncedSearch || requestKey !== loadedRequestKey;

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            setDebouncedSearch(search.trim());
            setPage(1);
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(timeout);
    }, [search]);

    useEffect(() => {
        const controller = new AbortController();
        const params = new URLSearchParams({
            search: debouncedSearch,
            page: String(page),
            per_page: String(CANDIDATES_PER_PAGE),
        });
        fetch(`/user-management/available-members?${params}`, { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then((response) => {
                if (!response.ok) throw new Error('Unable to load team members.');
                return response.json() as Promise<CandidatePage>;
            })
            .then(setCandidates)
            .catch((error: Error) => {
                if (error.name !== 'AbortError') setCandidates({ data: [], current_page: 1, last_page: 1, total: 0 });
            })
            .finally(() => setLoadedRequestKey(`${debouncedSearch}:${page}`));

        return () => controller.abort();
    }, [debouncedSearch, page]);

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
            <DialogContent className="grid h-[90vh] max-h-[760px] grid-rows-[auto_auto_minmax(0,1fr)_auto] gap-0 overflow-hidden p-0 sm:max-w-2xl">
                <DialogHeader className="px-6 pt-6 pr-12 pb-4">
                    <DialogTitle>Edit administrator team</DialogTitle>
                    <DialogDescription>
                        Select multiple users for your active-account team. Users assigned to another administrator remain visible but cannot be
                        selected.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-3 px-6 pb-4 sm:grid-cols-2">
                    <div className="border-border bg-secondary/70 rounded-xl border p-4">
                        <p className="font-semibold text-slate-900">{admin.name}</p>
                        <p className="truncate text-sm text-slate-600">{admin.email}</p>
                    </div>
                    <div className="border-border bg-secondary/60 min-h-0 rounded-xl border p-4">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="font-semibold text-slate-950">Members under you</p>
                                <p className="text-muted-foreground text-sm">
                                    {selected.length} {selected.length === 1 ? 'member' : 'members'} selected
                                </p>
                            </div>
                            <UsersRound className="text-primary size-5" />
                        </div>
                        {selected.length > 0 ? (
                            <div className="mt-3 flex max-h-20 flex-wrap gap-2 overflow-y-auto pr-1">
                                {selected.map((member) => (
                                    <button
                                        className="border-border text-secondary-foreground hover:bg-secondary inline-flex items-center gap-2 rounded-full border bg-white px-3 py-1.5 text-sm"
                                        key={member.id}
                                        onClick={() => toggleMember(member)}
                                        type="button"
                                    >
                                        {member.name}
                                        <X className="text-primary size-3.5" />
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground mt-3 text-sm">No members selected yet.</p>
                        )}
                    </div>
                </div>
                <div className="flex min-h-0 flex-col gap-3 px-6 pb-4">
                    <SearchInput onChange={(event) => setSearch(event.target.value)} placeholder="Search users by name or email" value={search} />
                    <DataLoadingOverlay
                        className="min-h-0 flex-1 overflow-hidden rounded-xl border"
                        contentClassName="flex h-full min-h-0 flex-col"
                        isLoading={loading}
                        label="Loading users..."
                    >
                        <div className="shrink-0 border-b bg-slate-50 px-4 py-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            Non-administrator users · {CANDIDATES_PER_PAGE} per page
                        </div>
                        {candidates.data.length > 0 ? (
                            <div className="min-h-0 flex-1 divide-y overflow-y-auto overscroll-contain">
                                {candidates.data.map((member) => {
                                    const isSelected = selected.some((selectedMember) => selectedMember.id === member.id);
                                    const ownedByAnotherAdmin = member.owner !== null && member.owner.id !== admin.id;

                                    return (
                                        <label
                                            className={`flex w-full items-center gap-3 px-4 py-3 text-left ${
                                                member.is_selectable ? 'hover:bg-secondary/60 cursor-pointer' : 'cursor-not-allowed bg-slate-50/70'
                                            }`}
                                            key={member.id}
                                        >
                                            <input
                                                aria-label={`Select ${member.name}`}
                                                checked={isSelected}
                                                className="accent-primary size-4 shrink-0"
                                                disabled={!member.is_selectable}
                                                onChange={() => toggleMember(member)}
                                                type="checkbox"
                                            />
                                            <span className="bg-secondary text-secondary-foreground flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
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
                                                    <span className="text-primary mt-1 block text-xs font-medium">Currently a member under you.</span>
                                                )}
                                            </span>
                                            <span className={`text-xs font-semibold ${ownedByAnotherAdmin ? 'text-amber-700' : 'text-primary'}`}>
                                                {ownedByAnotherAdmin ? 'Unavailable' : isSelected ? 'Selected' : 'Available'}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-muted-foreground flex min-h-0 flex-1 items-center justify-center p-8 text-center text-sm">
                                No users match this search.
                            </p>
                        )}
                    </DataLoadingOverlay>
                    <div className="flex shrink-0 items-center justify-between gap-3">
                        <p className="text-muted-foreground text-xs">
                            {candidates.total} users · Page {candidates.current_page} of {candidates.last_page}
                        </p>
                        <div className="flex gap-2">
                            <Button
                                disabled={candidates.current_page <= 1 || loading}
                                onClick={() => setPage(candidates.current_page - 1)}
                                size="sm"
                                type="button"
                                variant="outline"
                            >
                                Previous
                            </Button>
                            <Button
                                disabled={candidates.current_page >= candidates.last_page || loading}
                                onClick={() => setPage(candidates.current_page + 1)}
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
                <DialogFooter className="border-border bg-background shrink-0 border-t px-6 py-4">
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
