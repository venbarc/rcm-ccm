<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimConfigurationOption;
use App\Models\User;
use App\Services\ClaimConfigurationService;
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
                ->missing('cptSummary')
                ->missing('modmedStatusSummary')
                ->missing('payerBalance')
                ->missing('recentClaims'));
    }

    public function test_admin_dashboard_includes_cpt_and_modmed_financial_summaries()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $baseClaim = [
            'account_type' => AccountType::Tricity->value,
            'patient_name' => 'Admin Summary Patient',
            'service_date_start' => '2026-07-01',
            'work_status' => 'draft',
        ];
        ClaimConfigurationOption::query()->create([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
            'value' => 'Resolved/Paid',
            'label' => 'Resolved and Paid',
            'color' => '#DCFCE7',
            'sort_order' => 0,
        ]);
        ClaimConfigurationOption::query()->create([
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
            'value' => 'REVIEW NEEDED',
            'label' => 'Needs Review',
            'color' => '#FFEDD5',
            'sort_order' => 1,
        ]);

        Claim::query()->create($baseClaim + [
            'external_id' => 'SUMMARY-100',
            'procedure_code' => '99490',
            'modmed_claim_status' => 'Resolved/Paid',
            'units' => 1,
            'true_charge' => 100,
            'payments' => 80,
            'true_balance' => 20,
            'cf_invoice_amount' => 30,
        ]);
        Claim::query()->create($baseClaim + [
            'external_id' => 'SUMMARY-100',
            'procedure_code' => '99439',
            'modmed_claim_status' => 'Resolved/Paid',
            'units' => 2,
            'true_charge' => 50,
            'payments' => 20,
            'true_balance' => 30,
            'cf_invoice_amount' => 40,
        ]);
        Claim::query()->create($baseClaim + [
            'external_id' => 'SUMMARY-200',
            'procedure_code' => '99490',
            'modmed_claim_status' => 'REVIEW NEEDED',
            'units' => 3,
            'true_charge' => 200,
            'payments' => 50,
            'true_balance' => 150,
            'cf_invoice_amount' => 60,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/dashboard?preset=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('cptSummary.rows', 2)
                ->where('cptSummary.rows', function ($rows): bool {
                    $cpt = collect($rows)->firstWhere('group', '99490');

                    return $cpt !== null
                        && $cpt['billCount'] === 2
                        && $cpt['cptCount'] === 2
                        && $cpt['units'] === 4.0
                        && $cpt['trueCharge'] === 300.0
                        && $cpt['payments'] === 130.0
                        && $cpt['trueBalance'] === 170.0
                        && $cpt['collectionPercent'] === 43.33
                        && $cpt['cfInvoiceAmount'] === 90.0;
                })
                ->where('cptSummary.total.billCount', 2)
                ->where('cptSummary.total.cptCount', 3)
                ->where('cptSummary.total.units', 6.0)
                ->where('cptSummary.total.trueCharge', 350.0)
                ->where('cptSummary.total.payments', 150.0)
                ->where('cptSummary.total.trueBalance', 200.0)
                ->where('cptSummary.total.collectionPercent', 42.86)
                ->where('cptSummary.total.cfInvoiceAmount', 130.0)
                ->has('modmedStatusSummary.rows', 2)
                ->where('modmedStatusSummary.rows', function ($rows): bool {
                    $status = collect($rows)->firstWhere('group', 'Resolved/Paid');

                    return $status !== null
                        && $status['groupLabel'] === 'Resolved and Paid'
                        && $status['groupColor'] === '#DCFCE7'
                        && $status['billCount'] === 1
                        && $status['cptCount'] === 2
                        && $status['units'] === 3.0
                        && $status['trueCharge'] === 150.0
                        && $status['payments'] === 100.0
                        && $status['trueBalance'] === 50.0
                        && $status['collectionPercent'] === 66.67
                        && $status['cfInvoiceAmount'] === 70.0;
                }));
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
                ->has('claimsByStatus', 8)
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
                ->has('claimsByStatus', 8)
                ->where('claimsByStatus.0.status', 'draft')
                ->where('claimsByStatus.0.count', 1)
                ->missing('cptSummary')
                ->missing('modmedStatusSummary'));
    }
}
