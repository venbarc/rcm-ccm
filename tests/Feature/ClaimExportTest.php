<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimConfigurationOption;
use App\Models\ClaimExport;
use App\Models\ClaimImport;
use App\Models\ClaimRawRow;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\ClaimConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.default' => 'local',
            'queue.default' => 'sync',
            'claims.export.chunk_size' => 1,
        ]);
        Storage::fake('local');
    }

    public function test_claims_export_writes_one_row_per_cpt_line_and_is_account_scoped(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $agent = User::factory()->create(['name' => 'Assigned Agent']);
        GroupMember::query()->create([
            'admin_id' => $admin->id,
            'user_id' => $agent->id,
            'account_type' => AccountType::Tricity->value,
        ]);
        ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->where('value', 'yes')
            ->update(['label' => 'Approved']);
        ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->where('value', 'no')
            ->update(['label' => 'Declined']);

        $firstClaim = $this->claim([
            'external_id' => 'TC-EXPORT-1',
            'patient_name' => '=HYPERLINK("https://example.test")',
            'first_name' => 'Export',
            'last_name' => 'Patient',
            'patient_dob' => '1984-01-31',
            'patient_id' => 'MM0001',
            'procedure_code' => '99490',
            'location' => 'Hardy Oak Office',
            'payer_name' => 'Test Payer',
            'place_of_service_code' => '11 - Office',
            'primary_provider' => 'Test Provider',
            'modmed_claim_status' => 'REVIEW NEEDED',
            'cf_invoice_date' => '2026-06-30',
            'cf_invoice_amount' => 30,
            'payments' => 0,
            'true_balance' => 50.46,
            'true_charge' => 50.46,
            'units' => 1,
            'posted_date' => '2026-07-01',
            'service_date_start' => '2026-06-30',
            'work_status' => 'paid',
            'assigned_to' => $agent->id,
            'credit_status' => true,
            'credit_status_date' => '2026-07-29',
            'credit_reason' => 'not_covered_by_insurance',
        ]);
        ClaimRawRow::query()->create([
            'claim_id' => $firstClaim->id,
            'raw_payload' => [
                'cpt' => 99490,
                'location' => 'Hardy Oak Office',
                'bill_id' => 'TC-EXPORT-1',
                'invoice_rate_per_unit' => 30,
                'cf_invoice_amount' => 30,
                'payments' => 0,
                'true_balance' => 50.455,
                'true_charge' => 50.455,
                'units' => 1,
                'billingid_cpt' => 'TC-EXPORT-1-99490',
                'charges' => 185,
                'modmed_claim_status' => 'REVIEW NEEDED',
                'patient_first_name' => 'Export',
                'patient_last_name' => 'Patient',
                'patient_mrn' => 'MM0001',
                'patient_name' => '=HYPERLINK("https://example.test")',
                'payer' => 'Test Payer',
                'payer_cpt' => 'Test Payer-99490',
                'place_of_service_code' => '11 - Office',
                'primary_provider' => 'Test Provider',
                'true_charge_per_unit' => 50.46,
            ],
        ]);
        $this->claim([
            'external_id' => 'TC-EXPORT-1',
            'procedure_code' => '99439',
            'work_status' => 'draft',
            'true_charge' => 140,
            'credit_status' => false,
        ]);
        $this->claim([
            'external_id' => 'TC-EXPORT-1',
            'procedure_code' => 'G0511',
            'work_status' => 'draft',
            'true_charge' => 95,
        ]);
        $this->claim([
            'account_type' => AccountType::Principle->value,
            'external_id' => 'PR-HIDDEN-1',
            'procedure_code' => '99213',
            'work_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->postJson('/claims-export/start', ['type' => 'all']);

        $response->assertAccepted()
            ->assertJsonPath('export.status', 'completed')
            ->assertJsonPath('export.total_rows', 3)
            ->assertJsonPath('export.processed_rows', 3);

        $export = ClaimExport::query()->firstOrFail();
        Storage::assertExists($export->file_path);
        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim(Storage::get($export->file_path))));

        $this->assertCount(4, $rows);
        $this->assertSame([
            'CPT', 'Location', 'Bill ID', 'Invoice Rate Per Unit', 'CF Invoice Amount',
            'Payments', 'True Balance', 'True Charge', 'Units', 'BillingID-CPT',
            'Charges', 'ModMed_Claim_Status', 'CF Invoice Date', 'Patient DOB',
            'Patient First Name', 'Patient Last Name', 'Patient MRN', 'Patient Name',
            'Payer', 'Payer-CPT', 'Place of Service Code', 'Posted Date Month/Year',
            'Primary Provider', 'Service Date', 'True Charge Per Unit',
        ], array_slice($rows[0], 0, 25));
        $this->assertSame([
            'Work Status', 'Assigned To', 'Denial Reason', 'Notes',
            'Invoiced Status', 'Invoiced Status Date', 'Credit Status',
            'Credit Status Date', 'Credit Reason', 'Last Updated',
        ], array_slice($rows[0], 25));
        $this->assertSame(['99490', '99439', 'G0511'], array_column(array_slice($rows, 1), 0));
        $this->assertSame('30', $rows[1][3]);
        $this->assertSame('50.46', $rows[1][6]);
        $this->assertSame('50.46', $rows[1][7]);
        $this->assertSame('TC-EXPORT-1-99490', $rows[1][9]);
        $this->assertSame('185', $rows[1][10]);
        $this->assertSame('\'=HYPERLINK("https://example.test")', $rows[1][17]);
        $this->assertSame('Invoiced', $rows[1][array_search('Invoiced Status', $rows[0], true)]);
        $this->assertSame('2026-06-30', $rows[1][array_search('Invoiced Status Date', $rows[0], true)]);
        $this->assertSame('Approved', $rows[1][array_search('Credit Status', $rows[0], true)]);
        $this->assertSame('2026-07-29', $rows[1][array_search('Credit Status Date', $rows[0], true)]);
        $this->assertSame('Not Covered by the Insurance', $rows[1][array_search('Credit Reason', $rows[0], true)]);
        $this->assertSame('Declined', $rows[2][array_search('Credit Status', $rows[0], true)]);
        $this->assertSame('', $rows[2][array_search('Credit Status Date', $rows[0], true)]);
        $this->assertSame('', $rows[2][array_search('Credit Reason', $rows[0], true)]);
        $this->assertSame('--', $rows[3][array_search('Credit Status', $rows[0], true)]);
        $this->assertSame('', $rows[3][array_search('Credit Status Date', $rows[0], true)]);
        $this->assertSame('', $rows[3][array_search('Credit Reason', $rows[0], true)]);
        $this->assertSame('Paid', $rows[1][array_search('Work Status', $rows[0], true)]);
        $this->assertSame('Assigned Agent', $rows[1][array_search('Assigned To', $rows[0], true)]);
        $this->assertStringNotContainsString('PR-HIDDEN-1', Storage::get($export->file_path));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get("/claims-export/{$export->id}/download")
            ->assertDownload($export->file_name);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->getJson("/claims-export/{$export->id}/progress")
            ->assertNotFound();
    }

    public function test_claims_export_supports_status_and_assignee_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $agent = User::factory()->create(['name' => 'Assigned Agent']);
        GroupMember::query()->create([
            'admin_id' => $admin->id,
            'user_id' => $agent->id,
            'account_type' => AccountType::Tricity->value,
        ]);

        $this->claim([
            'external_id' => 'TC-PAID-1',
            'procedure_code' => '99490',
            'work_status' => 'paid',
            'assigned_to' => $agent->id,
        ]);
        $this->claim([
            'external_id' => 'TC-DRAFT-1',
            'procedure_code' => '99439',
            'work_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->postJson('/claims-export/start', [
                'type' => 'status',
                'status' => 'paid',
            ])
            ->assertAccepted()
            ->assertJsonPath('export.total_rows', 1);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->postJson('/claims-export/start', [
                'type' => 'assignee',
                'assigned_to' => (string) $agent->id,
            ])
            ->assertAccepted()
            ->assertJsonPath('export.total_rows', 1);

        $this->assertSame(2, ClaimExport::query()->where('status', 'completed')->count());
    }

    public function test_claims_export_is_blocked_during_an_active_import(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->claim();
        ClaimImport::query()->create([
            'account_type' => AccountType::Tricity->value,
            'file_name' => 'active.csv',
            'status' => 'processing',
            'imported_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->postJson('/claims-export/start', ['type' => 'all'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('export');

        $this->assertSame(0, ClaimExport::query()->count());
    }

    public function test_claims_import_is_blocked_during_an_active_export(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        ClaimExport::query()->create([
            'account_type' => AccountType::Tricity->value,
            'user_id' => $admin->id,
            'file_name' => 'active.csv',
            'file_path' => 'claim-exports/active.csv',
            'status' => 'processing',
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/claims-import', [
                'file' => UploadedFile::fake()->createWithContent(
                    'claims.csv',
                    "Patient Name,CPT/Product,Claim ID\nTest Patient,99490,TC-1",
                ),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, ClaimImport::query()->count());
        $this->assertSame([], Storage::allFiles('claim-imports'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function claim(array $overrides = []): Claim
    {
        return Claim::query()->create(array_merge([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TC-DEFAULT-1',
            'patient_name' => 'Export Patient',
            'procedure_code' => '99490',
            'work_status' => 'draft',
        ], $overrides));
    }
}
