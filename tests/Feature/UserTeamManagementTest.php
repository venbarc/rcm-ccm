<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
