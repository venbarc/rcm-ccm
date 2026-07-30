<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_all_member_candidates_but_only_select_unassigned_or_owned_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $currentMember = User::factory()->create(['name' => 'Current Member']);
        $availableUser = User::factory()->create(['name' => 'Available User']);
        $ownedByAnotherAdmin = User::factory()->create(['name' => 'Protected User']);

        $admin->members()->attach($currentMember->id, ['account_type' => AccountType::Tricity->value]);
        $otherAdmin->members()->attach($ownedByAnotherAdmin->id, ['account_type' => AccountType::Tricity->value]);

        $response = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/user-management/available-members')
            ->assertOk();

        $candidates = collect($response->json('data'))->keyBy('name');
        $this->assertTrue($candidates['Current Member']['is_selectable']);
        $this->assertTrue($candidates['Current Member']['is_selected']);
        $this->assertTrue($candidates['Available User']['is_selectable']);
        $this->assertFalse($candidates['Available User']['is_selected']);
        $this->assertFalse($candidates['Protected User']['is_selectable']);
        $this->assertFalse($candidates['Protected User']['is_selected']);
        $this->assertSame($otherAdmin->name, $candidates['Protected User']['owner']['name']);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/user-management/available-members?search=Protected')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Protected User')
            ->assertJsonPath('data.0.is_selectable', false)
            ->assertJsonPath('data.0.owner.name', $otherAdmin->name);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$admin->id}/members", [
                'member_ids' => [$availableUser->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('group_members', [
            'admin_id' => $admin->id,
            'user_id' => $currentMember->id,
            'account_type' => AccountType::Tricity->value,
        ]);
        $this->assertDatabaseHas('group_members', [
            'admin_id' => $admin->id,
            'user_id' => $availableUser->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $this->from('/user-management')
            ->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$admin->id}/members", [
                'member_ids' => [$ownedByAnotherAdmin->id],
            ])
            ->assertRedirect('/user-management')
            ->assertSessionHasErrors('member_ids');

        $this->assertSame($otherAdmin->id, GroupMember::query()
            ->where('user_id', $ownedByAnotherAdmin->id)
            ->where('account_type', AccountType::Tricity->value)
            ->value('admin_id'));
    }

    public function test_admin_cannot_manage_another_administrators_team(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$otherAdmin->id}/members", ['member_ids' => []])
            ->assertForbidden();
    }

    public function test_admin_index_shows_member_ownership_and_supports_membership_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Current Admin']);
        $otherAdmin = User::factory()->create(['is_admin' => true, 'name' => 'Other Admin']);
        $currentMember = User::factory()->create(['name' => 'Current Member']);
        $availableUser = User::factory()->create(['name' => 'Available User']);
        $ownedByAnotherAdmin = User::factory()->create(['name' => 'Protected User']);

        GroupMember::create([
            'admin_id' => $admin->id,
            'user_id' => $currentMember->id,
            'account_type' => AccountType::Tricity->value,
        ]);
        GroupMember::create([
            'admin_id' => $otherAdmin->id,
            'user_id' => $ownedByAnotherAdmin->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.team', '')
                ->has('users.data', 4)
                ->where('users.data.0.name', 'Current Admin')
                ->where('users.data.0.members_under_you_count', 1)
                ->where('users.data', function ($users): bool {
                    $users = collect($users);
                    $protected = $users->firstWhere('name', 'Protected User');

                    return $users->pluck('name')->contains('Current Member')
                        && $users->pluck('name')->contains('Available User')
                        && $protected['can_manage'] === false
                        && $protected['admins'][0]['name'] === 'Other Admin';
                }));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management?team=mine')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.team', 'mine')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Current Member'));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management?team=unassigned')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.team', 'unassigned')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Available User'));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management?team=other')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.team', 'other')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Protected User'));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management?role=user')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.role', 'user')
                ->where('users.total', 3)
                ->where('summary.users', 3)
                ->where('summary.admins', 1));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/user-management?role=admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.role', 'admin')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Current Admin'));
    }

    public function test_user_access_updates_accounts_but_prohibits_role_changes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$user->id}", [
                'account_types' => [AccountType::Tricity->value],
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertSame([AccountType::Tricity->value], $user->fresh()->account_types);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/user-management')
            ->patch("/user-management/{$user->id}", [
                'is_admin' => true,
                'account_types' => AccountType::values(),
            ])
            ->assertRedirect('/user-management')
            ->assertSessionHasErrors('is_admin');

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_admin_cannot_update_another_admin_or_a_user_owned_by_another_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $protectedUser = User::factory()->create();

        GroupMember::create([
            'admin_id' => $otherAdmin->id,
            'user_id' => $protectedUser->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $payload = [
            'account_types' => [AccountType::Tricity->value],
        ];

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$otherAdmin->id}", [
                'account_types' => AccountType::values(),
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$protectedUser->id}", $payload)
            ->assertForbidden();
    }
}
