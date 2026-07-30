<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\ClaimActivityService;
use App\Services\TeamService;
use App\Support\CurrentAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly ClaimActivityService $activities,
        private readonly TeamService $teams,
    ) {}

    public function index(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $currentUser = $request->user();
        $search = trim($request->string('search')->toString());
        $role = $request->string('role')->toString();
        $team = $request->string('team')->toString();
        $query = $this->teams->visibleUsersQuery($currentUser, $account->value)
            ->with(['admins' => fn ($admins) => $admins
                ->wherePivot('account_type', $account->value)
                ->select('users.id', 'users.name', 'users.email')])
            ->withCount(['members as members_under_you_count' => fn ($members) => $members
                ->where('group_members.account_type', $account->value)]);

        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role === 'admin') {
            $query->where('is_admin', true);
        } elseif ($role === 'user') {
            $query->where('is_admin', false);
        }

        if ($team === 'mine') {
            $query->whereHas('groupMembershipsAsMember', fn (Builder $membership) => $membership
                ->where('account_type', $account->value)
                ->where('admin_id', $currentUser->id));
        } elseif ($team === 'unassigned') {
            $query->where('is_admin', false)
                ->whereDoesntHave('groupMembershipsAsMember', fn (Builder $membership) => $membership
                    ->where('account_type', $account->value));
        } elseif ($team === 'other') {
            $query->whereHas('groupMembershipsAsMember', fn (Builder $membership) => $membership
                ->where('account_type', $account->value)
                ->where('admin_id', '!=', $currentUser->id));
        }

        $visibleUsers = $this->teams->visibleUsersQuery($currentUser, $account->value);
        $users = $query
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$currentUser->id])
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(function (User $user) use ($currentUser, $account): User {
                $user->setAttribute('can_manage', $this->teams->canManageUser($currentUser, $user, $account->value));

                return $user;
            });

        return Inertia::render('users/index', [
            'users' => $users,
            'accountTypes' => AccountType::options(),
            'filters' => compact('search', 'role', 'team'),
            'myTeamMembers' => $currentUser->membersForAccount($account->value)
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.email']),
            'summary' => [
                'total' => (clone $visibleUsers)->count(),
                'admins' => (clone $visibleUsers)->where('is_admin', true)->count(),
                'users' => (clone $visibleUsers)->where('is_admin', false)->count(),
                'myTeam' => GroupMember::query()
                    ->where('admin_id', $currentUser->id)
                    ->where('account_type', $account->value)
                    ->count(),
            ],
        ]);
    }

    public function availableMembers(Request $request): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $search = trim($request->string('search')->toString());
        $perPage = min(max($request->integer('per_page', 10), 5), 50);
        $currentUser = $request->user();
        $query = $this->teams->availableMembersQuery($currentUser, $account->value)
            ->with(['admins' => fn ($admins) => $admins
                ->wherePivot('account_type', $account->value)
                ->select('users.id', 'users.name', 'users.email')]);

        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->through(function (User $user) use ($currentUser): array {
                $owner = $user->admins->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'owner' => $owner?->only(['id', 'name', 'email']),
                    'is_selectable' => $owner === null || $owner->is($currentUser),
                    'is_selected' => $owner?->is($currentUser) ?? false,
                ];
            });

        return response()->json($members);
    }

    public function members(Request $request, User $user): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $this->authorizeTeamOwner($request, $user);

        return response()->json([
            'data' => $user->membersForAccount($account->value)
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.email']),
        ]);
    }

    public function syncMembers(Request $request, User $user): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        $this->authorizeTeamOwner($request, $user);
        $validated = $request->validate([
            'member_ids' => ['present', 'array'],
            'member_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $before = $user->membersForAccount($account->value)->pluck('users.id')->all();

        $this->teams->sync($user, $account->value, $validated['member_ids']);

        $this->activities->record(
            $account->value,
            'team_updated',
            "Updated team members for {$user->email}",
            $request->user(),
            before: ['member_ids' => $before],
            after: ['member_ids' => array_values(array_map('intval', $validated['member_ids']))],
        );

        return back()->with('success', 'Team members updated.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $account = CurrentAccount::resolve($request);
        $this->authorizeManageUser($request, $user, $account->value);
        abort_if($user->is_admin, 422, 'Administrator roles and account access are managed by OneAccess.');
        $validated = $request->validate([
            'is_admin' => ['prohibited'],
            'account_types' => ['required', 'array'],
            'account_types.*' => ['string', 'in:'.implode(',', AccountType::values())],
        ]);

        $before = $user->only(array_keys($validated));

        DB::transaction(function () use ($user, $validated): void {
            $user->update($validated);
            $user->groupMembershipsAsMember()
                ->whereNotIn('account_type', $validated['account_types'])
                ->delete();
        });

        $this->activities->record($account->value, 'user_updated', "Updated access for {$user->email}", $request->user(), before: $before, after: $validated);

        return back()->with('success', 'User access updated.');
    }

    private function authorizeTeamOwner(Request $request, User $user): void
    {
        abort_unless($user->is($request->user()) && $user->is_admin, 403, 'Administrators may only manage their own team.');
    }

    private function authorizeManageUser(Request $request, User $user, string $account): void
    {
        abort_unless(
            $this->teams->canManageUser($request->user(), $user, $account),
            403,
            'Administrators may only manage themselves, their team members, or unassigned users for the active account.',
        );
    }
}
