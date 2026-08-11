<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\ClaimConfigurationOption;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\ClaimConfigurationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_worked_date_filter_uses_claim_edits_and_pacific_calendar_days(): void
    {
        $user = User::factory()->create();

        try {
            Carbon::setTestNow(Carbon::parse('2026-08-11 18:00:00', 'UTC'));
            $pacificAugustEleventh = Claim::query()->create([
                'account_type' => AccountType::Tricity->value,
                'external_id' => 'TC-WORKED-AUG-11',
                'patient_name' => 'Pacific August Eleven',
                'procedure_code' => '11111',
            ]);
            $pacificAugustTwelfth = Claim::query()->create([
                'account_type' => AccountType::Tricity->value,
                'external_id' => 'TC-WORKED-AUG-12',
                'patient_name' => 'Pacific August Twelve',
                'procedure_code' => '22222',
            ]);
            Claim::query()->create([
                'account_type' => AccountType::Tricity->value,
                'external_id' => 'TC-IMPORT-ONLY',
                'patient_name' => 'Import Timestamp Only',
                'procedure_code' => '33333',
            ]);

            // 06:30 UTC is still August 11 in Los Angeles during daylight saving time.
            Carbon::setTestNow(Carbon::parse('2026-08-12 06:30:00', 'UTC'));
            ClaimActivity::query()->create([
                'account_type' => AccountType::Tricity->value,
                'claim_id' => $pacificAugustEleventh->id,
                'user_id' => $user->id,
                'action' => 'claim_updated',
                'description' => 'Worked late on August 11 Pacific time',
            ]);

            // 07:30 UTC has crossed into August 12 in Los Angeles.
            Carbon::setTestNow(Carbon::parse('2026-08-12 07:30:00', 'UTC'));
            ClaimActivity::query()->create([
                'account_type' => AccountType::Tricity->value,
                'claim_id' => $pacificAugustTwelfth->id,
                'user_id' => $user->id,
                'action' => 'claim_updated',
                'description' => 'Worked on August 12 Pacific time',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $query = http_build_query([
            'worked_from' => '2026-08-11',
            'worked_to' => '2026-08-11',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get("/claims?{$query}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.worked_from', '2026-08-11')
                ->where('filters.worked_to', '2026-08-11')
                ->where('claims.total', 1)
                ->where('claims.data.0.bill_id', 'TC-WORKED-AUG-11')
                ->where('summary.totalCount', 1));
    }

    public function test_claims_can_filter_by_multiple_payers_selected_from_a_keyword_search(): void
    {
        $user = User::factory()->create();

        foreach ([
            ['bill_id' => 'TC-PAYER-1', 'payer_name' => 'Aetna Texas Medicaid & CHIP'],
            ['bill_id' => 'TC-PAYER-2', 'payer_name' => 'Superior Medicaid'],
            ['bill_id' => 'TC-PAYER-3', 'payer_name' => 'Medicare of Texas'],
        ] as $claim) {
            Claim::query()->create([
                'account_type' => AccountType::Tricity->value,
                'external_id' => $claim['bill_id'],
                'patient_name' => 'Payer Filter Patient',
                'payer_name' => $claim['payer_name'],
                'procedure_code' => '99490',
            ]);
        }

        $selectedPayers = json_encode(['Aetna Texas Medicaid & CHIP', 'Superior Medicaid'], JSON_THROW_ON_ERROR);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims?'.http_build_query(['payer_name' => $selectedPayers]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.payer_name', $selectedPayers)
                ->where('claims.total', 2)
                ->where('claims.data', fn ($claims): bool => collect($claims)
                    ->pluck('payer_name')
                    ->sort()
                    ->values()
                    ->all() === ['Aetna Texas Medicaid & CHIP', 'Superior Medicaid']));

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/claims/options?filter=payer_name&search=medicaid&per_page=200')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_claims_can_filter_by_credit_status_date_and_reason(): void
    {
        $user = User::factory()->create();
        $yesStatus = ClaimConfigurationOption::query()->updateOrCreate([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::CREDIT_STATUS,
            'value' => 'yes',
        ], [
            'label' => 'Yes',
            'sort_order' => 0,
        ]);
        $noStatus = ClaimConfigurationOption::query()->updateOrCreate([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::CREDIT_STATUS,
            'value' => 'no',
        ], [
            'label' => 'No',
            'sort_order' => 1,
        ]);
        $inactiveInsurance = ClaimConfigurationOption::query()->updateOrCreate([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::CREDIT_REASON,
            'value' => 'inactive_insurance',
        ], [
            'label' => 'Inactive Insurance',
            'sort_order' => 0,
        ]);
        $notCovered = ClaimConfigurationOption::query()->updateOrCreate([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::CREDIT_REASON,
            'value' => 'not_covered_by_insurance',
        ], [
            'label' => 'Not Covered by the Insurance',
            'sort_order' => 1,
        ]);

        Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-CREDIT-FILTER-MATCH',
            'patient_name' => 'Credit Filter Match',
            'procedure_code' => '99490',
            'credit_status' => true,
            'credit_status_id' => $yesStatus->id,
            'credit_status_date' => '2026-07-15',
            'credit_reason' => 'inactive_insurance',
            'credit_reason_id' => $inactiveInsurance->id,
        ]);
        Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-CREDIT-FILTER-OLD',
            'patient_name' => 'Credit Filter Old',
            'procedure_code' => '99439',
            'credit_status' => true,
            'credit_status_id' => $yesStatus->id,
            'credit_status_date' => '2026-06-15',
            'credit_reason' => 'not_covered_by_insurance',
            'credit_reason_id' => $notCovered->id,
        ]);
        Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-CREDIT-FILTER-NO',
            'patient_name' => 'Credit Filter No',
            'procedure_code' => '98980',
            'credit_status' => false,
            'credit_status_id' => $noStatus->id,
        ]);

        $query = http_build_query([
            'credit_status' => 'yes',
            'credit_status_from' => '2026-07-01',
            'credit_status_to' => '2026-07-31',
            'credit_reason' => 'inactive_insurance',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get("/claims?{$query}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.credit_status', 'yes')
                ->where('filters.credit_status_from', '2026-07-01')
                ->where('filters.credit_status_to', '2026-07-31')
                ->where('filters.credit_reason', 'inactive_insurance')
                ->where('claims.total', 1)
                ->where('claims.data.0.bill_id', 'TC-CREDIT-FILTER-MATCH')
                ->where('creditStatuses.0.value', 'yes')
                ->where('creditReasons.0.value', 'inactive_insurance'));
    }

    public function test_authorized_user_can_assign_a_tricity_claim(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
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

    public function test_tricity_claim_lines_expose_payments_on_index_and_detail_views(): void
    {
        $user = User::factory()->create();
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-PAYMENTS-1',
            'patient_name' => 'Payment Patient',
            'procedure_code' => '99490',
            'payments' => 25.75,
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('claims.data.0.lines.0.payments', fn ($value) => (float) $value === 25.75));

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get("/claims/{$claim->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('claim.lines.0.payments', fn ($value) => (float) $value === 25.75));
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
            'before' => ['work_status' => 'draft', 'assigned_to' => null],
            'after' => ['work_status' => 'appeal', 'assigned_to' => $user->id],
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
            ->where('activities.1.before.assigned_to', 'Unassigned')
            ->where('activities.1.after.assigned_to', 'Claim Editor')
            ->where('returnTo', $returnTo));
    }

    public function test_claim_line_update_assigns_only_the_selected_cpt_line_to_the_editor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $previousOwner = User::factory()->create();
        GroupMember::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $firstLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-UPD-1',
            'patient_name' => 'Grouped Patient',
            'procedure_code' => '99490',
            'cf_invoice_date' => '2026-07-01',
            'work_status' => 'draft',
            'assigned_to' => $previousOwner->id,
        ]);
        $secondLine = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-UPD-1',
            'patient_name' => 'Grouped Patient',
            'procedure_code' => '99439',
            'cf_invoice_date' => '2026-07-02',
            'work_status' => 'draft',
        ]);
        ClaimConfigurationOption::query()->create([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::DENIAL_REASON,
            'value' => 'Missing documentation',
            'label' => 'Missing documentation',
            'sort_order' => 0,
            'added_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/claims/{$firstLine->id}?page=2&search=Grouped&expanded={$secondLine->id}", [
                'work_status' => 'appeal',
                'denial_reason' => 'Missing documentation',
                'notes' => 'Followed up with payer',
                'invoiced_status' => 'credited',
                'invoiced_status_date' => '2026-07-30',
                'credit_status' => true,
                'credit_status_date' => '2026-07-29',
                'credit_reason' => 'inactive_insurance',
            ])
            ->assertRedirect("/claims?page=2&search=Grouped&expanded={$firstLine->id}");

        $this->assertSame('appeal', $firstLine->fresh()->work_status);
        $this->assertSame('Missing documentation', $firstLine->fresh()->denial_reason);
        $this->assertSame('Followed up with payer', $firstLine->fresh()->notes);
        $this->assertSame('invoiced', $firstLine->fresh()->invoiced_status);
        $this->assertSame('2026-07-01', $firstLine->fresh()->invoiced_status_date?->toDateString());
        $this->assertTrue($firstLine->fresh()->credit_status);
        $this->assertSame('2026-07-29', $firstLine->fresh()->credit_status_date?->toDateString());
        $this->assertSame('inactive_insurance', $firstLine->fresh()->credit_reason);
        $this->assertTrue($firstLine->fresh()->work_status_manually_set);
        $this->assertSame($user->id, $firstLine->fresh()->assigned_to);
        $this->assertSame('in_progress', $firstLine->fresh()->status);

        $this->assertSame('draft', $secondLine->fresh()->work_status);
        $this->assertNull($secondLine->fresh()->denial_reason);
        $this->assertNull($secondLine->fresh()->notes);
        $this->assertSame('invoiced', $secondLine->fresh()->invoiced_status);
        $this->assertSame('2026-07-02', $secondLine->fresh()->invoiced_status_date?->toDateString());
        $this->assertNull($secondLine->fresh()->credit_status);
        $this->assertNull($secondLine->fresh()->credit_status_date);
        $this->assertNull($secondLine->fresh()->credit_reason);
        $this->assertNull($secondLine->fresh()->assigned_to);
        $this->assertFalse((bool) $secondLine->fresh()->work_status_manually_set);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims?invoiced_status=invoiced')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.invoiced_status', 'invoiced')
                ->where('claims.total', 1)
                ->where('claims.data.0.lines.0.invoiced_status', 'invoiced')
                ->where('claims.data.0.lines.0.invoiced_status_date', '2026-07-01')
                ->where('claims.data.0.lines.0.credit_status', true)
                ->where('claims.data.0.lines.0.credit_status_date', '2026-07-29')
                ->where('claims.data.0.lines.0.credit_reason', 'inactive_insurance')
                ->where('invoicedStatuses.0.value', 'invoiced')
                ->has('invoicedStatuses', 1)
                ->where('creditReasons.0.value', 'inactive_insurance')
                ->where('creditReasons.1.value', 'not_covered_by_insurance'));

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/claims/options?filter=invoiced_status_date')
            ->assertStatus(422);
    }

    public function test_credit_status_requires_a_date_and_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        GroupMember::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'account_type' => AccountType::Tricity->value,
        ]);
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
                'credit_status' => true,
                'credit_status_date' => null,
                'credit_reason' => null,
            ])
            ->assertRedirect('/claims')
            ->assertSessionHasErrors([
                'credit_status_date' => 'Credit Status Date is required when Credit Status is Yes.',
                'credit_reason' => 'Credit Reason is required when Credit Status is Yes.',
            ]);

        $this->assertSame('invoiced', $claim->fresh()->invoiced_status);
        $this->assertNull($claim->fresh()->credit_status);
        $this->assertNull($claim->fresh()->credit_status_date);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/claims')
            ->patch("/claims/{$claim->id}", [
                'credit_status' => false,
                'credit_status_date' => null,
                'credit_reason' => null,
            ])
            ->assertRedirect('/claims')
            ->assertSessionDoesntHaveErrors();

        $this->assertFalse($claim->fresh()->credit_status);
        $this->assertNull($claim->fresh()->credit_status_date);
        $this->assertNull($claim->fresh()->credit_reason);
    }

    public function test_user_without_an_admin_cannot_update_claim_lines(): void
    {
        $user = User::factory()->create();
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-NO-ADMIN-1',
            'patient_name' => 'Unassigned Team User',
            'procedure_code' => '99490',
            'work_status' => 'draft',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canEditClaims', false));

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/claims')
            ->patch("/claims/{$claim->id}", [
                'work_status' => 'appeal',
                'notes' => 'This update must be rejected.',
            ])
            ->assertRedirect('/claims')
            ->assertSessionHasErrors([
                'claim' => 'You are not assigned to an administrator. Ask an administrator to add you as a member before editing claims.',
            ]);

        $claim->refresh();
        $this->assertSame('draft', $claim->work_status);
        $this->assertNull($claim->notes);
        $this->assertNull($claim->assigned_to);
        $this->assertSame(0, ClaimActivity::query()->where('claim_id', $claim->id)->count());
    }
}
