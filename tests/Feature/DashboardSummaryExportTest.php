<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\User;
use App\Support\AccountContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardSummaryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoiced_summary_export_matches_the_panel_invoice_date_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->tricityClaim([
            'external_id' => 'INVOICED-EXPORT-JUNE-1',
            'procedure_code' => '99490',
            'cf_invoice_date' => '2026-06-15',
            'units' => 3,
        ]);
        $this->tricityClaim([
            'external_id' => 'INVOICED-EXPORT-JUNE-2',
            'procedure_code' => '99490',
            'cf_invoice_date' => '2026-06-20',
            'units' => 2,
        ]);
        $this->tricityClaim([
            'external_id' => 'INVOICED-EXPORT-JUNE-3',
            'procedure_code' => '99439',
            'cf_invoice_date' => '2026-06-30',
            'units' => 1,
        ]);
        $this->tricityClaim([
            'external_id' => 'INVOICED-EXPORT-JULY',
            'procedure_code' => '99439',
            'cf_invoice_date' => '2026-07-01',
            'units' => 9,
        ]);
        $this->tricityClaim([
            'external_id' => 'INVOICED-EXPORT-NEVER-INVOICED',
            'procedure_code' => '98980',
            'cf_invoice_date' => null,
            'units' => 7,
        ]);

        $response = $this->exportAs($admin, AccountType::Tricity->value, 'invoiced-summary', [
            'invoiced_invoice_start' => '2026-06-01',
            'invoiced_invoice_end' => '2026-06-30',
        ]);

        $response->assertOk();
        $this->assertSame([
            ['CPT Code', 'Units'],
            ['99490', '5'],
            ['99439', '1'],
            ['Grand Total', '6'],
        ], $this->csvRows($response));
        $this->assertStringContainsString(
            'tricity_pain_associates_invoiced_summary_2026-06-01_to_2026-06-30',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_credit_status_summary_export_matches_the_panel_credit_status_date_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->tricityClaim([
            'external_id' => 'CREDIT-EXPORT-JULY-1',
            'procedure_code' => '99439',
            'credit_status' => true,
            'credit_status_date' => '2026-07-15',
            'units' => 2,
            'true_charge' => 200,
            'cf_invoice_amount' => 40,
        ]);
        $this->tricityClaim([
            'external_id' => 'CREDIT-EXPORT-JULY-2',
            'procedure_code' => '99439',
            'credit_status' => true,
            'credit_status_date' => '2026-07-31',
            'units' => 1,
            'true_charge' => 100.5,
            'cf_invoice_amount' => 20,
        ]);
        $this->tricityClaim([
            'external_id' => 'CREDIT-EXPORT-AUGUST',
            'procedure_code' => '99490',
            'credit_status' => true,
            'credit_status_date' => '2026-08-07',
            'units' => 5,
            'true_charge' => 500,
            'cf_invoice_amount' => 50,
        ]);
        $this->tricityClaim([
            'external_id' => 'CREDIT-EXPORT-NOT-CREDITED',
            'procedure_code' => '98985',
            'credit_status' => false,
            'units' => 4,
            'true_charge' => 400,
        ]);

        $response = $this->exportAs($admin, AccountType::Tricity->value, 'credit-status-summary', [
            'credit_status_invoice_start' => '2026-07-01',
            'credit_status_invoice_end' => '2026-07-31',
        ]);

        $response->assertOk();
        $this->assertSame([
            ['CPT Code', 'Count of CPT', 'Sum of Units', 'Sum of True Charge', 'Sum of CF Invoice Amount'],
            ['99439', '2', '3', '300.5', '60'],
            ['Grand Total', '2', '3', '300.5', '60'],
        ], $this->csvRows($response));
    }

    public function test_summary_exports_use_principle_labels_and_service_dates(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        AccountContext::runWith(AccountType::Principle->value, function (): void {
            foreach ([
                ['id' => 'PRINCIPLE-EXPORT-JULY', 'date' => '2026-07-31', 'units' => 2],
                ['id' => 'PRINCIPLE-EXPORT-AUGUST', 'date' => '2026-08-10', 'units' => 6],
            ] as $claim) {
                Claim::query()->create([
                    'account_type' => AccountType::Principle->value,
                    'external_id' => $claim['id'],
                    'primary_claim_id' => $claim['id'],
                    'patient_name' => 'Principle Summary Export Patient',
                    'procedure_code' => '99490',
                    'date_of_service' => $claim['date'],
                    'cf_invoice_amount' => 30,
                    'units' => $claim['units'],
                ]);
            }
        });

        $invoiced = $this->exportAs($admin, AccountType::Principle->value, 'invoiced-summary', [
            'invoiced_service_start' => '2026-07-01',
            'invoiced_service_end' => '2026-07-31',
        ]);

        $invoiced->assertOk();
        $this->assertSame([
            ['Procedure Code', 'Units'],
            ['99490', '2'],
            ['Grand Total', '2'],
        ], $this->csvRows($invoiced));
        $this->assertStringContainsString(
            'principle_spine_and_pain_invoiced_summary_2026-07-01_to_2026-07-31',
            (string) $invoiced->headers->get('content-disposition'),
        );

        $credit = $this->exportAs($admin, AccountType::Principle->value, 'credit-status-summary');

        $credit->assertOk();
        $this->assertSame([
            ['Procedure Code', 'Count of CPT', 'Sum of Units', 'Sum of True Charge', 'Sum of CF Invoice Amount'],
            ['Grand Total', '0', '0', '0', '0'],
        ], $this->csvRows($credit));
        $this->assertStringContainsString('all_dates', (string) $credit->headers->get('content-disposition'));
    }

    public function test_summary_exports_are_admin_only_and_reject_unknown_panels(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->tricityClaim(['external_id' => 'EXPORT-GUARD-1', 'cf_invoice_date' => '2026-06-15']);

        $this->actingAs($member)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/dashboard-export/invoiced-summary')
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/dashboard-export/cpt-summary')
            ->assertNotFound();
    }

    /** @param array<string, string> $filters */
    private function exportAs(User $user, string $account, string $panel, array $filters = []): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['account_type' => $account])
            ->get("/dashboard-export/{$panel}?".http_build_query($filters));
    }

    /** @return array<int, array<int, string|null>> */
    private function csvRows(TestResponse $response): array
    {
        $content = trim($response->streamedContent());

        return array_map(
            fn (string $line): array => str_getcsv($line, ',', '"', '\\'),
            array_values(array_filter(preg_split('/\r\n|\r|\n/', $content) ?: [])),
        );
    }

    /** @param array<string, mixed> $overrides */
    private function tricityClaim(array $overrides = []): Claim
    {
        return Claim::query()->create(array_merge([
            'account_type' => AccountType::Tricity->value,
            'patient_name' => 'Summary Export Patient',
            'procedure_code' => '99490',
            'work_status' => 'draft',
            'units' => 1,
        ], $overrides));
    }
}
