<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\GroupMember;
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
        GroupMember::create(['admin_id' => $admin->id, 'user_id' => $agent->id, 'account_type' => AccountType::Tricity->value]);
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-1001',
            'patient_name' => 'Test Patient',
            'balance' => 250,
            'procedure_code' => '99490',
        ]);
        $secondLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-1001',
            'patient_name' => 'Test Patient',
            'balance' => 100,
            'procedure_code' => '99439',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments', [
                'claim_ids' => [$claim->id],
                'user_id' => $agent->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('claims', [
            'id' => $secondLine->id,
            'assigned_to' => $agent->id,
            'status' => 'in_progress',
        ]);
        $this->assertSame(2, ClaimActivity::query()->where('action', 'assigned')->count());
    }

    public function test_assignment_page_reports_claim_id_and_cpt_line_counts_per_assignee(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Manager']);
        $agent = User::factory()->create(['name' => 'Assigned Agent']);
        GroupMember::create(['admin_id' => $admin->id, 'user_id' => $agent->id, 'account_type' => AccountType::Tricity->value]);

        foreach (['99490', '99439'] as $procedureCode) {
            Claim::create([
                'account_type' => AccountType::Tricity->value,
                'external_id' => 'TC-ASSIGNED-1',
                'patient_name' => 'Assigned Patient',
                'procedure_code' => $procedureCode,
                'assigned_to' => $agent->id,
                'true_balance' => 50,
            ]);
        }
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-UNASSIGNED-1',
            'patient_name' => 'Unassigned Patient',
            'procedure_code' => '99213',
            'true_balance' => 25,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/assignments');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('assignments/index')
            ->where('summary.claim_groups', 1)
            ->where('summary.claim_lines', 1)
            ->where('summary.assigned_claim_groups', 1)
            ->where('summary.assigned_claim_lines', 2)
            ->where('summary.assigned_total_true_balance', 100.0)
            ->where('groupDefinitions', fn ($definitions): bool => collect($definitions)->pluck('key')->doesntContain('service_type'))
            ->has('assignmentWorkloads', 2)
            ->where('assignmentWorkloads', function ($workloads) use ($agent): bool {
                $agentWorkload = collect($workloads)->firstWhere('id', $agent->id);

                return $agentWorkload !== null
                    && $agentWorkload['claim_groups'] === 1
                    && $agentWorkload['claim_lines'] === 2
                    && $agentWorkload['total_true_balance'] === 100.0;
            }));
    }

    public function test_assignment_page_counts_every_cpt_line_and_balance_in_a_partially_assigned_bill_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Manager']);
        $agent = User::factory()->create(['name' => 'Assigned Agent']);
        GroupMember::create(['admin_id' => $admin->id, 'user_id' => $agent->id, 'account_type' => AccountType::Tricity->value]);

        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-PARTIAL-1',
            'patient_name' => 'Partially Assigned Patient',
            'procedure_code' => '99490',
            'assigned_to' => $agent->id,
            'true_balance' => 50,
        ]);
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-PARTIAL-1',
            'patient_name' => 'Partially Assigned Patient',
            'procedure_code' => '99439',
            'true_balance' => 75,
        ]);
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-UNASSIGNED-2',
            'patient_name' => 'Unassigned Patient',
            'procedure_code' => '99213',
            'true_balance' => 25,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/assignments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.claim_groups', 1)
                ->where('summary.claim_lines', 1)
                ->where('summary.total_true_balance', 25.0)
                ->where('summary.assigned_claim_groups', 1)
                ->where('summary.assigned_claim_lines', 2)
                ->where('summary.assigned_total_true_balance', 125.0)
                ->where('assignmentWorkloads', function ($workloads) use ($agent): bool {
                    $agentWorkload = collect($workloads)->firstWhere('id', $agent->id);

                    return $agentWorkload !== null
                        && $agentWorkload['claim_groups'] === 1
                        && $agentWorkload['claim_lines'] === 2
                        && $agentWorkload['total_true_balance'] === 125.0;
                }));
    }

    public function test_distribution_filters_by_cpt_but_keeps_every_claim_id_line_together(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $agentA = User::factory()->create(['name' => 'Agent A']);
        $agentB = User::factory()->create(['name' => 'Agent B']);
        foreach ([$agentA, $agentB] as $agent) {
            GroupMember::create(['admin_id' => $admin->id, 'user_id' => $agent->id, 'account_type' => AccountType::Tricity->value]);
        }

        $firstLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-2001',
            'patient_name' => 'First Patient',
            'procedure_code' => '99490',
            'true_balance' => 100,
        ]);
        $secondLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-2001',
            'patient_name' => 'First Patient',
            'procedure_code' => '99439',
            'true_balance' => 20,
        ]);
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-2002',
            'patient_name' => 'Second Patient',
            'procedure_code' => '99490',
            'true_balance' => 80,
        ]);

        $preview = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/assignments/preview?group_by=procedure_code&group_values[]=99490&user_ids[]='.$agentA->id.'&user_ids[]='.$agentB->id);

        $preview->assertOk()
            ->assertJsonPath('total_claims', 2)
            ->assertJsonPath('total_lines', 2)
            ->assertJsonPath('target_balance', 90);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments/distribute', [
                'group_by' => 'procedure_code',
                'group_values' => ['99490'],
                'user_ids' => [$agentA->id, $agentB->id],
            ])
            ->assertRedirect();

        $firstOwner = $firstLine->fresh()->assigned_to;
        $this->assertNotNull($firstOwner);
        $this->assertSame($firstOwner, $secondLine->fresh()->assigned_to);
        $this->assertNotSame($firstOwner, Claim::query()->where('external_id', 'TC-2002')->value('assigned_to'));
    }

    public function test_distribution_options_are_searchable_and_paginated_for_dropdown_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-OPT-1',
            'patient_name' => 'Option One',
            'procedure_code' => '99490',
            'true_balance' => 100,
        ]);
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-OPT-2',
            'patient_name' => 'Option Two',
            'procedure_code' => '99439',
            'true_balance' => 25,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/assignments/options?group_by=procedure_code&per_page=1&page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', '99439')
            ->assertJsonPath('data.0.count', 1)
            ->assertJsonPath('data.0.balance', 25.0)
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('total', 2);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/assignments/options?group_by=procedure_code&search=99490')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', '99490')
            ->assertJsonPath('data.0.balance', 100.0);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/assignments/options?group_by=service_type')
            ->assertUnprocessable();
    }

    public function test_missing_true_balance_uses_even_group_counts_without_splitting_claims(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $agents = User::factory()->count(2)->create();
        foreach ($agents as $agent) {
            GroupMember::create(['admin_id' => $admin->id, 'user_id' => $agent->id, 'account_type' => AccountType::Tricity->value]);
        }

        foreach (['TC-3001', 'TC-3002', 'TC-3003'] as $externalId) {
            foreach (['99490', '99439'] as $code) {
                Claim::create([
                    'account_type' => AccountType::Tricity->value,
                    'external_id' => $externalId,
                    'patient_name' => 'Grouped Patient',
                    'procedure_code' => $code,
                    'true_balance' => null,
                ]);
            }
        }

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/assignments/distribute', [
                'group_by' => 'all',
                'group_values' => [],
                'user_ids' => $agents->pluck('id')->all(),
            ])
            ->assertRedirect();

        $groups = Claim::query()->get()->groupBy('external_id');
        foreach ($groups as $lines) {
            $this->assertCount(1, $lines->pluck('assigned_to')->unique());
        }
        $this->assertEqualsCanonicalizing([1, 2], $groups->map(fn ($lines) => $lines->first()->assigned_to)->countBy()->values()->all());
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

    public function test_claims_index_groups_cpt_lines_under_one_claim_id(): void
    {
        $user = User::factory()->create();

        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-GRP-1',
            'patient_name' => 'Grouped Patient',
            'patient_id' => 'MRN-1',
            'procedure_code' => '99490',
            'true_charge' => 100,
            'true_balance' => 75,
            'payments' => 20,
            'service_date_start' => '2026-06-01',
        ]);
        Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-GRP-1',
            'patient_name' => 'Grouped Patient',
            'patient_id' => 'MRN-1',
            'procedure_code' => '99439',
            'true_charge' => 50,
            'true_balance' => 30,
            'payments' => 10,
            'service_date_start' => '2026-06-02',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('claims/index')
            ->has('claims.data', 1)
            ->where('claims.data.0.bill_id', 'TC-GRP-1')
            ->where('claims.data.0.line_count', 2)
            ->where('claims.data.0.true_charge', 150.0)
            ->where('claims.data.0.true_balance', 105.0)
            ->where('summary.totalCount', 2)
            ->has('claims.data.0.lines', 2));
    }

    public function test_claim_view_groups_cpt_lines_and_their_activity_logs(): void
    {
        $user = User::factory()->create(['name' => 'Claim Editor']);
        $firstLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-VIEW-1',
            'patient_name' => 'Viewed Patient',
            'patient_id' => 'MRN-VIEW',
            'procedure_code' => '99490',
            'true_charge' => 100,
            'payments' => 20,
            'adjustments' => 5,
            'true_balance' => 75,
            'work_status' => 'appeal',
            'service_date_start' => '2026-06-01',
        ]);
        $secondLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-VIEW-1',
            'patient_name' => 'Viewed Patient',
            'patient_id' => 'MRN-VIEW',
            'procedure_code' => '99439',
            'true_charge' => 50,
            'payments' => 10,
            'adjustments' => 2,
            'true_balance' => 38,
            'work_status' => 'paid',
            'service_date_start' => '2026-06-02',
        ]);

        ClaimActivity::create([
            'account_type' => AccountType::Tricity->value,
            'claim_id' => $firstLine->id,
            'user_id' => $user->id,
            'action' => 'claim_updated',
            'description' => 'Updated CPT 99490',
            'before' => ['work_status' => 'draft'],
            'after' => ['work_status' => 'appeal'],
        ]);
        ClaimActivity::create([
            'account_type' => AccountType::Tricity->value,
            'claim_id' => $secondLine->id,
            'user_id' => $user->id,
            'action' => 'assigned',
            'description' => 'Assigned CPT 99439',
        ]);

        $returnTo = "/claims?page=3&expanded={$firstLine->id}";
        $response = $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get("/claims/{$firstLine->id}?return_to=".urlencode($returnTo));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('claims/show')
            ->where('claim.bill_id', 'TC-VIEW-1')
            ->where('claim.line_count', 2)
            ->where('claim.total_true_charge', 150.0)
            ->where('claim.total_payments', 30.0)
            ->where('claim.total_true_balance', 113.0)
            ->has('claim.lines', 2)
            ->has('activities', 2)
            ->where('activities.0.cpt_code', '99439')
            ->where('activities.1.cpt_code', '99490')
            ->where('returnTo', $returnTo));
    }

    public function test_claim_line_update_assigns_only_the_selected_cpt_line_to_the_editor(): void
    {
        $user = User::factory()->create();
        $previousOwner = User::factory()->create();

        $firstLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-UPD-1',
            'patient_name' => 'Grouped Patient',
            'procedure_code' => '99490',
            'work_status' => 'draft',
            'assigned_to' => $previousOwner->id,
        ]);
        $secondLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-UPD-1',
            'patient_name' => 'Grouped Patient',
            'procedure_code' => '99439',
            'work_status' => 'draft',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/claims/{$firstLine->id}?page=2&search=Grouped&expanded={$secondLine->id}", [
                'work_status' => 'appeal',
                'denial_reason' => 'Missing documentation',
                'notes' => 'Followed up with payer',
                'invoiced_status' => 'pending_credit',
                'invoiced_status_date' => '2026-07-29',
                'credit_reason' => 'inactive_insurance',
            ])
            ->assertRedirect("/claims?page=2&search=Grouped&expanded={$firstLine->id}");

        $this->assertSame('appeal', $firstLine->fresh()->work_status);
        $this->assertSame('Missing documentation', $firstLine->fresh()->denial_reason);
        $this->assertSame('Followed up with payer', $firstLine->fresh()->notes);
        $this->assertSame('pending_credit', $firstLine->fresh()->invoiced_status);
        $this->assertSame('2026-07-29', $firstLine->fresh()->invoiced_status_date?->toDateString());
        $this->assertSame('inactive_insurance', $firstLine->fresh()->credit_reason);
        $this->assertTrue($firstLine->fresh()->work_status_manually_set);
        $this->assertSame($user->id, $firstLine->fresh()->assigned_to);
        $this->assertSame('in_progress', $firstLine->fresh()->status);

        $this->assertSame('draft', $secondLine->fresh()->work_status);
        $this->assertNull($secondLine->fresh()->denial_reason);
        $this->assertNull($secondLine->fresh()->notes);
        $this->assertNull($secondLine->fresh()->invoiced_status);
        $this->assertNull($secondLine->fresh()->invoiced_status_date);
        $this->assertNull($secondLine->fresh()->credit_reason);
        $this->assertNull($secondLine->fresh()->assigned_to);
        $this->assertFalse((bool) $secondLine->fresh()->work_status_manually_set);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims?invoiced_status=pending_credit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.invoiced_status', 'pending_credit')
                ->where('claims.total', 1)
                ->where('claims.data.0.lines.0.invoiced_status', 'pending_credit')
                ->where('claims.data.0.lines.0.invoiced_status_date', '2026-07-29')
                ->where('claims.data.0.lines.0.credit_reason', 'inactive_insurance')
                ->where('invoicedStatuses.0.value', 'invoiced')
                ->where('invoicedStatuses.1.value', 'pending_credit')
                ->where('invoicedStatuses.2.value', 'credited')
                ->where('creditReasons.0.value', 'inactive_insurance')
                ->where('creditReasons.1.value', 'not_covered_by_insurance'));

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims?invoiced_status_from=2026-07-29&invoiced_status_to=2026-07-29')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.invoiced_status_from', '2026-07-29')
                ->where('filters.invoiced_status_to', '2026-07-29')
                ->where('claims.total', 1));

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims?invoiced_status_from=2026-07-30&invoiced_status_to=2026-07-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('claims.total', 0));
    }

    public function test_pending_credit_and_credited_statuses_require_a_credit_reason(): void
    {
        $user = User::factory()->create();
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-CREDIT-VALIDATION-1',
            'patient_name' => 'Credit Validation Patient',
            'procedure_code' => '99490',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/claims')
            ->patch("/claims/{$claim->id}", [
                'invoiced_status' => 'credited',
                'invoiced_status_date' => '2026-07-29',
                'credit_reason' => null,
            ])
            ->assertRedirect('/claims')
            ->assertSessionHasErrors('credit_reason');

        $this->assertNull($claim->fresh()->invoiced_status);
    }
}
