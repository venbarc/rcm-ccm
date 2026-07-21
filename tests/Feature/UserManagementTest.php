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

    public function test_admin_can_manage_only_available_users_on_their_active_account_team(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $currentMember = User::factory()->create(['name' => 'Current Member']);
        $availableUser = User::factory()->create(['name' => 'Available User']);
        $ownedByAnotherAdmin = User::factory()->create(['name' => 'Protected User']);

        $admin->members()->attach($currentMember->id, ['account_type' => AccountType::Tricity->value]);
        $otherAdmin->members()->attach($ownedByAnotherAdmin->id, ['account_type' => AccountType::Tricity->value]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/user-management/available-members')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Current Member'])
            ->assertJsonFragment(['name' => 'Available User'])
            ->assertJsonMissing(['name' => 'Protected User']);

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

    public function test_admin_index_only_shows_their_team_and_unassigned_users_for_the_active_account(): void
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
            ->assertSee('Current Admin')
            ->assertSee('Current Member')
            ->assertSee('Available User')
            ->assertDontSee('Other Admin')
            ->assertDontSee('Protected User');
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
            'is_admin' => false,
            'can_assign_claims' => false,
            'account_types' => [AccountType::Tricity->value],
        ];

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$otherAdmin->id}", [
                'is_admin' => true,
                'can_assign_claims' => true,
                'account_types' => AccountType::values(),
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$protectedUser->id}", $payload)
            ->assertForbidden();
    }
}
