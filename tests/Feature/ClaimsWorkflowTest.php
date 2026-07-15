<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_assign_a_tricity_claim(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'can_assign_claims' => true]);
        $agent = User::factory()->create();
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-1001',
            'patient_name' => 'Test Patient',
            'balance' => 250,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments', [
                'claim_ids' => [$claim->id],
                'user_id' => $agent->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'assigned_to' => $agent->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('claim_activities', [
            'claim_id' => $claim->id,
            'action' => 'assigned',
        ]);
    }

    public function test_claims_from_another_account_are_not_exposed(): void
    {
        $user = User::factory()->create();
        Claim::create([
            'account_type' => AccountType::Principle->value,
            'external_id' => 'PR-1001',
            'patient_name' => 'Hidden Patient',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('claims/index')
            ->has('claims.data', 0));
        $this->assertSame(0, ClaimActivity::count());
    }
}
