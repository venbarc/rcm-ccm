<?php

namespace App\Services;

use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function visibleUsersQuery(User $admin, string $account): Builder
    {
        abort_unless($admin->is_admin, 422, 'Only administrators can manage users.');

        return User::query()
            ->where(function (Builder $query) use ($admin, $account): void {
                $query->whereKey($admin->id)
                    ->orWhere(function (Builder $members) use ($account): void {
                        $members->where('is_admin', false)
                            ->whereJsonContains('account_types', $account);
                    });
            });
    }

    public function canManageUser(User $admin, User $target, string $account): bool
    {
        if (! $admin->is_admin) {
            return false;
        }

        if ($target->is($admin)) {
            return true;
        }

        if ($target->is_admin || ! $target->canAccessAccount($account)) {
            return false;
        }

        $ownerId = GroupMember::query()
            ->where('user_id', $target->id)
            ->where('account_type', $account)
            ->value('admin_id');

        return $ownerId === null || (int) $ownerId === $admin->id;
    }

    /** @return Collection<int, User> */
    public function assignmentCandidates(User $manager, string $account): Collection
    {
        if (! $manager->is_admin) {
            return $manager->canAccessAccount($account)
                ? User::query()->whereKey($manager->id)->get(['id', 'name', 'email'])
                : new Collection;
        }

        return User::query()
            ->where(function (Builder $query) use ($manager, $account): void {
                $query->whereKey($manager->id)
                    ->orWhereHas('admins', function (Builder $admins) use ($manager, $account): void {
                        $admins->whereKey($manager->id)
                            ->where('group_members.account_type', $account);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function availableMembersQuery(User $admin, string $account): Builder
    {
        abort_unless($admin->is_admin, 422, 'Only administrators can manage users.');

        return User::query()
            ->where('is_admin', false)
            ->whereJsonContains('account_types', $account);
    }

    /** @param array<int, int|string> $memberIds */
    public function sync(User $admin, string $account, array $memberIds): void
    {
        abort_unless($admin->is_admin, 422, 'Only administrators can own a team.');

        $ids = array_values(array_unique(array_map('intval', $memberIds)));
        $eligibleIds = User::query()
            ->whereIn('id', $ids)
            ->where('is_admin', false)
            ->whereJsonContains('account_types', $account)
            ->pluck('id')
            ->all();

        if (count($eligibleIds) !== count($ids)) {
            throw ValidationException::withMessages([
                'member_ids' => 'Choose only non-admin users with access to the active account.',
            ]);
        }

        $ownedByAnotherAdmin = GroupMember::query()
            ->where('account_type', $account)
            ->whereIn('user_id', $ids)
            ->where('admin_id', '!=', $admin->id)
            ->exists();

        if ($ownedByAnotherAdmin) {
            throw ValidationException::withMessages([
                'member_ids' => 'One or more users already belong to another administrator.',
            ]);
        }

        DB::transaction(function () use ($admin, $account, $ids): void {
            GroupMember::query()
                ->where('admin_id', $admin->id)
                ->where('account_type', $account)
                ->when($ids !== [], fn (Builder $query) => $query->whereNotIn('user_id', $ids))
                ->delete();

            foreach ($ids as $userId) {
                GroupMember::query()->updateOrCreate(
                    ['user_id' => $userId, 'account_type' => $account],
                    ['admin_id' => $admin->id],
                );
            }
        });
    }
}
