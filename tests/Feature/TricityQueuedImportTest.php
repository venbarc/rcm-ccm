<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimConfigurationOption;
use App\Models\ClaimImport;
use App\Models\ClaimRawRow;
use App\Models\User;
use App\Services\ClaimConfigurationService;
use App\Services\ClaimImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TricityQueuedImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'local']);
        Storage::fake('local');
    }

    public function test_tricity_rows_are_chunked_by_bill_id_and_raw_source_columns_are_retained(): void
    {
        config(['queue.default' => 'sync', 'claims.import.chunk_size' => 1]);
        $user = User::factory()->create(['is_admin' => true]);

        $import = app(ClaimImportService::class)->queue(
            $this->tricityFile(),
            AccountType::Tricity->value,
            $user,
        )->fresh();

        $this->assertSame('completed', $import->status);
        $this->assertSame(2, $import->total_rows);
        $this->assertSame(2, $import->processed_rows);
        $this->assertSame(2, $import->created_count);
        $this->assertSame(2, Claim::count());
        $this->assertSame(2, ClaimRawRow::count());
        $this->assertSame(2, Claim::query()->whereNull('true_balance')->count());
        $this->assertSame(2, Claim::query()->where('bill_id', 'BILL-1')->count());
        $this->assertSame(2, Claim::query()->where('external_id', 'BILL-1')->count());
        $claim = Claim::query()->where('procedure_code', '99490')->firstOrFail();
        $this->assertSame('Smith, Alex', $claim->primary_provider);
        $this->assertSame('REVIEW NEEDED', $claim->modmed_claim_status);
        $this->assertFalse($claim->modmed_claim_status_manually_set);
        $this->assertDatabaseHas('claim_configuration_options', [
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
            'value' => 'REVIEW NEEDED',
            'label' => 'REVIEW NEEDED',
            'added_by' => null,
        ]);
        $this->assertNotNull(ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::MODMED_CLAIM_STATUS)
            ->where('value', 'REVIEW NEEDED')
            ->value('color'));
        $this->assertSame('2026-07-01', $claim->cf_invoice_date?->toDateString());
        $this->assertSame('30.00', $claim->cf_invoice_amount);
        $this->assertSame('invoiced', $claim->invoiced_status);
        $this->assertSame('2026-07-01', $claim->invoiced_status_date?->toDateString());
        $this->assertNull($claim->credit_status);
        $this->assertNull($claim->credit_status_date);
        $this->assertSame('Superior Medicaid', $claim->rawRow?->raw_payload['payer']);
        $this->assertArrayHasKey('posted_date_month_year', $claim->rawRow?->raw_payload ?? []);
        $this->assertNull($import->stored_path);
        $this->assertSame([], Storage::allFiles('claim-imports'));
    }

    public function test_reimport_updates_source_data_and_retains_worked_fields(): void
    {
        config(['queue.default' => 'sync', 'claims.import.chunk_size' => 1]);
        $user = User::factory()->create(['is_admin' => true]);
        $imports = app(ClaimImportService::class);
        $imports->queue($this->tricityFile(), AccountType::Tricity->value, $user);

        $claim = Claim::query()->where('procedure_code', '99490')->firstOrFail();
        app(ClaimConfigurationService::class)->resolveModMedClaimStatus(AccountType::Tricity->value, 'Resolved/Paid');
        $claim->update([
            'work_status' => 'appeal',
            'work_status_manually_set' => true,
            'denial_reason' => 'Missing authorization',
            'notes' => 'Appeal submitted.',
            'credit_status' => true,
            'credit_status_date' => '2026-07-15',
            'credit_reason' => 'inactive_insurance',
            'modmed_claim_status' => 'Resolved/Paid',
            'modmed_claim_status_manually_set' => true,
        ]);

        $secondImport = $imports->queue($this->tricityUpdatedFile(), AccountType::Tricity->value, $user)->fresh();
        $claim->refresh();

        $this->assertSame(0, $secondImport->created_count);
        $this->assertSame(2, $secondImport->updated_count);
        $this->assertSame('appeal', $claim->work_status);
        $this->assertSame('Missing authorization', $claim->denial_reason);
        $this->assertSame('Appeal submitted.', $claim->notes);
        $this->assertSame('invoiced', $claim->invoiced_status);
        $this->assertSame('2026-07-01', $claim->invoiced_status_date?->toDateString());
        $this->assertTrue($claim->credit_status);
        $this->assertSame('2026-07-15', $claim->credit_status_date?->toDateString());
        $this->assertSame('inactive_insurance', $claim->credit_reason);
        $this->assertSame('Resolved/Paid', $claim->modmed_claim_status);
        $this->assertTrue($claim->modmed_claim_status_manually_set);
        $this->assertSame('250.00', $claim->true_charge);
        $this->assertNull($claim->true_balance);
    }

    public function test_reimport_overrides_untouched_source_fields_and_keeps_unworked_rows_in_draft(): void
    {
        config(['queue.default' => 'sync', 'claims.import.chunk_size' => 1]);
        $user = User::factory()->create(['is_admin' => true]);
        $imports = app(ClaimImportService::class);
        $imports->queue($this->tricityFile(), AccountType::Tricity->value, $user);

        $claim = Claim::query()->where('procedure_code', '99490')->firstOrFail();
        $this->assertSame('draft', $claim->work_status);
        $this->assertSame('185.00', $claim->true_charge);

        $imports->queue($this->tricityUpdatedFile(), AccountType::Tricity->value, $user);
        $claim->refresh();

        $this->assertSame('250.00', $claim->true_charge);
        $this->assertSame('Payment Pending', $claim->modmed_claim_status);
        $this->assertFalse($claim->modmed_claim_status_manually_set);
        $this->assertSame('draft', $claim->work_status);
        $this->assertFalse($claim->work_status_manually_set);
        $this->assertSame([], Storage::allFiles('claim-imports'));
    }

    public function test_failed_import_rolls_back_before_removing_its_source_file(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $path = 'claim-imports/failed.csv';
        Storage::put($path, 'Bill ID');
        $import = ClaimImport::query()->create([
            'account_type' => AccountType::Tricity->value,
            'file_name' => 'failed.csv',
            'stored_path' => $path,
            'status' => 'processing',
            'imported_by' => $user->id,
            'started_at' => now(),
        ]);

        app(ClaimImportService::class)->failImport($import->id, 'Test import failure.');
        $import->refresh();

        $this->assertSame('failed', $import->status);
        $this->assertSame('Test import failure.', $import->error_message);
        $this->assertNull($import->stored_path);
        Storage::assertMissing($path);
    }

    public function test_a_new_cpt_line_inherits_its_existing_claim_group_assignee(): void
    {
        config(['queue.default' => 'sync', 'claims.import.chunk_size' => 1]);
        $admin = User::factory()->create(['is_admin' => true]);
        $agent = User::factory()->create();
        $imports = app(ClaimImportService::class);
        $imports->queue($this->tricityFile(), AccountType::Tricity->value, $admin);
        Claim::query()->where('bill_id', 'BILL-1')->update(['assigned_to' => $agent->id]);

        $imports->queue($this->tricityFileWithExtraLine(), AccountType::Tricity->value, $admin);

        $this->assertSame(3, Claim::query()->where('bill_id', 'BILL-1')->count());
        $this->assertSame(3, Claim::query()->where('bill_id', 'BILL-1')->where('assigned_to', $agent->id)->count());
    }

    private function tricityFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tricity.csv', implode("\n", [
            'CPT,Location,Bill ID,Payments,True Balance,True Charge,ModMed_Claim_Status,CF Invoice Date,CF Invoice Amount,Patient First Name,Patient Last Name,Patient Name,Patient MRN,Payer,Place of Service Code,Posted Date Month/Year,Primary Provider,Service Date,Units',
            '99490,Hardy Oak Office,BILL-1,0,,185,REVIEW NEEDED,2026-07-01,30,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,11 - Office,2026-07-01,"Smith, Alex",2026-07-01,1',
            '99439,Hardy Oak Office,BILL-1,0,,140,REVIEW NEEDED,2026-07-01,40,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,11 - Office,2026-07-01,"Smith, Alex",2026-07-01,1',
        ]));
    }

    private function tricityFileWithExtraLine(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tricity-expanded.csv', implode("\n", [
            'CPT,Location,Bill ID,Payments,True Balance,True Charge,ModMed_Claim_Status,CF Invoice Date,Patient First Name,Patient Last Name,Patient Name,Patient MRN,Payer,Primary Provider,Service Date,Units',
            '99490,Hardy Oak Office,BILL-1,0,,185,REVIEW NEEDED,2026-07-01,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,"Smith, Alex",2026-07-01,1',
            '99439,Hardy Oak Office,BILL-1,0,,140,REVIEW NEEDED,2026-07-01,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,"Smith, Alex",2026-07-01,1',
            'G0511,Hardy Oak Office,BILL-1,0,,95,REVIEW NEEDED,2026-07-01,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,"Smith, Alex",2026-07-01,1',
        ]));
    }

    private function tricityUpdatedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tricity-updated.csv', implode("\n", [
            'CPT,Location,Bill ID,Payments,True Balance,True Charge,ModMed_Claim_Status,CF Invoice Date,Patient First Name,Patient Last Name,Patient Name,Patient MRN,Payer,Primary Provider,Service Date,Units',
            '99490,Hardy Oak Office,BILL-1,35,,250,Payment Pending,2026-07-01,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,"Smith, Alex",2026-07-01,1',
            '99439,Hardy Oak Office,BILL-1,20,,175,Payment Pending,2026-07-01,Jamie,Doe,"Doe, Jamie",MRN-1,Superior Medicaid,"Smith, Alex",2026-07-01,1',
        ]));
    }
}
