<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_select_users_for_their_active_account_team(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $members = User::factory()->count(2)->create();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/user-management/{$admin->id}/members", [
                'member_ids' => $members->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($members as $member) {
            $this->assertDatabaseHas('group_members', [
                'admin_id' => $admin->id,
                'user_id' => $member->id,
                'account_type' => AccountType::Tricity->value,
            ]);
        }
    }

    public function test_an_admin_cannot_take_a_user_owned_by_another_admin(): void
    {
        $firstAdmin = User::factory()->create(['is_admin' => true]);
        $secondAdmin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        GroupMember::create([
            'admin_id' => $firstAdmin->id,
            'user_id' => $member->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $this->actingAs($secondAdmin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/user-management')
            ->patch("/user-management/{$secondAdmin->id}/members", [
                'member_ids' => [$member->id],
            ])
            ->assertRedirect('/user-management')
            ->assertSessionHasErrors('member_ids');

        $this->assertDatabaseHas('group_members', [
            'admin_id' => $firstAdmin->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_claim_assignments_only_accept_users_from_the_managers_team(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $outsideUser = User::factory()->create();
        GroupMember::create([
            'admin_id' => $admin->id,
            'user_id' => $member->id,
            'account_type' => AccountType::Tricity->value,
        ]);
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-TEAM-1',
            'patient_name' => 'Team Patient',
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments', ['claim_ids' => [$claim->id], 'user_id' => $outsideUser->id])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments', ['claim_ids' => [$claim->id], 'user_id' => $member->id])
            ->assertRedirect();

        $this->assertSame($member->id, $claim->fresh()->assigned_to);
    }

    public function test_non_admin_users_cannot_assign_claims(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-USER-ASSIGNMENT-1',
            'patient_name' => 'User Assignment Patient',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments', ['claim_ids' => [$claim->id], 'user_id' => $user->id])
            ->assertForbidden();

        $this->assertNull($claim->fresh()->assigned_to);
    }

    public function test_navbar_identifies_the_users_admin_for_the_active_account(): void
    {
        $admin = User::factory()->create([
            'name' => 'Account Administrator',
            'is_admin' => true,
        ]);
        $user = User::factory()->create();
        GroupMember::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminMembership.id', $admin->id)
                ->where('adminMembership.name', 'Account Administrator'));
    }

    public function test_navbar_reports_when_the_user_has_no_admin_for_the_active_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminMembership', null));
    }
}
