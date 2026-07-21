<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('http://oneaccess.test/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['account_type' => AccountType::Tricity->value]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('accountLabel', AccountType::Tricity->label())
                ->where('filters.preset', 'all')
                ->has('workSummary')
                ->has('claimsByStatus')
                ->missing('payerBalance')
                ->missing('recentClaims'));
    }

    public function test_dashboard_matches_tfc_line_metrics_and_service_date_filtering()
    {
        $user = User::factory()->create();
        $baseClaim = [
            'account_type' => AccountType::Tricity->value,
            'patient_name' => 'Dashboard Patient',
            'payer_name' => 'Primary Payer',
            'status' => 'in_progress',
            'balance' => 0,
        ];

        Claim::query()->create($baseClaim + [
            'external_id' => 'DASH-100',
            'procedure_code' => '99201',
            'service_date_start' => '2026-01-10',
            'true_balance' => 100,
            'work_status' => 'appeal',
        ]);
        Claim::query()->create($baseClaim + [
            'external_id' => 'DASH-100',
            'procedure_code' => '99202',
            'service_date_start' => '2026-01-11',
            'true_balance' => 50,
            'work_status' => 'paid',
        ]);
        Claim::query()->create($baseClaim + [
            'external_id' => 'DASH-200',
            'procedure_code' => '99203',
            'service_date_start' => '2026-02-01',
            'true_balance' => 200,
            'work_status' => 'draft',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value]);

        $this->get('/dashboard?preset=custom&start=2026-01-01&end=2026-01-31')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('filters.preset', 'custom')
                ->where('workSummary.totalCount', 2)
                ->where('workSummary.totalAmount', 150.0)
                ->where('workSummary.workedCount', 2)
                ->where('workSummary.workedAmount', 150.0)
                ->where('workSummary.remainingCount', 1)
                ->where('workSummary.remainingAmount', 100.0)
                ->where('workSummary.paidCount', 1)
                ->where('workSummary.paidAmount', 50.0)
                ->where('workSummary.workedProgress', 50)
                ->has('claimsByStatus', 9)
                ->where('claimsByStatus.0.status', 'draft')
                ->where('claimsByStatus.0.count', 0)
                ->where('claimsByStatus.1.status', 'paid')
                ->where('claimsByStatus.1.count', 1)
                ->missing('payerBalance')
                ->missing('recentClaims'));
    }

    public function test_dashboard_counts_imported_claim_lines_without_balance_values()
    {
        $user = User::factory()->create();

        Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'DASH-NO-BALANCE',
            'procedure_code' => '99490',
            'service_date_start' => '2026-06-10',
            'true_charge' => 185,
            'true_balance' => null,
            'balance' => null,
            'work_status' => 'draft',
        ]);

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value]);

        $this->get('/dashboard?preset=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('workSummary.totalCount', 1)
                ->where('workSummary.totalAmount', 0.0)
                ->where('workSummary.workedCount', 0)
                ->where('workSummary.remainingCount', 1)
                ->has('claimsByStatus', 9)
                ->where('claimsByStatus.0.status', 'draft')
                ->where('claimsByStatus.0.count', 1));
    }
}
