<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimImport;
use App\Models\ClaimRawRow;
use App\Models\User;
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

    public function test_tricity_rows_are_chunked_and_missing_financial_columns_remain_null(): void
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
        $this->assertSame(2, Claim::query()->where('external_id', 'TC-1001')->count());
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
        $claim->update([
            'work_status' => 'appeal',
            'work_status_manually_set' => true,
            'denial_reason' => 'Missing authorization',
            'notes' => 'Appeal submitted.',
        ]);

        $secondImport = $imports->queue($this->tricityUpdatedFile(), AccountType::Tricity->value, $user)->fresh();
        $claim->refresh();

        $this->assertSame(0, $secondImport->created_count);
        $this->assertSame(2, $secondImport->updated_count);
        $this->assertSame('appeal', $claim->work_status);
        $this->assertSame('Missing authorization', $claim->denial_reason);
        $this->assertSame('Appeal submitted.', $claim->notes);
        $this->assertSame('250.00', $claim->true_charge);
        $this->assertNull($claim->true_balance);
    }

    public function test_reimport_overrides_untouched_source_fields_and_refreshes_automatic_status(): void
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
        $this->assertSame('historical_posted_payments', $claim->work_status);
        $this->assertFalse($claim->work_status_manually_set);
        $this->assertSame([], Storage::allFiles('claim-imports'));
    }

    public function test_failed_import_rolls_back_before_removing_its_source_file(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $path = 'claim-imports/failed.csv';
        Storage::put($path, 'Claim ID');
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
        Claim::query()->where('external_id', 'TC-1001')->update(['assigned_to' => $agent->id]);

        $imports->queue($this->tricityFileWithExtraLine(), AccountType::Tricity->value, $admin);

        $this->assertSame(3, Claim::query()->where('external_id', 'TC-1001')->count());
        $this->assertSame(3, Claim::query()->where('external_id', 'TC-1001')->where('assigned_to', $agent->id)->count());
    }

    private function tricityFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tricity.csv', implode("\n", [
            'Patient First Name,Patient Last Name,Patient Name,CPT/Product,Service Date,Charges,Bill ID,Activity Type,Claim ID,Patient MRN,Payer,Primary Provider',
            'Jamie,Doe,"Doe, Jamie",99490,2026-07-01,185,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
            'Jamie,Doe,"Doe, Jamie",99439,2026-07-01,140,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
        ]));
    }

    private function tricityFileWithExtraLine(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tricity-expanded.csv', implode("\n", [
            'Patient First Name,Patient Last Name,Patient Name,CPT/Product,Service Date,Charges,Bill ID,Activity Type,Claim ID,Patient MRN,Payer,Primary Provider',
            'Jamie,Doe,"Doe, Jamie",99490,2026-07-01,185,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
            'Jamie,Doe,"Doe, Jamie",99439,2026-07-01,140,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
            'Jamie,Doe,"Doe, Jamie",G0511,2026-07-01,95,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
        ]));
    }

    private function tricityUpdatedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tricity-updated.csv', implode("\n", [
            'Patient First Name,Patient Last Name,Patient Name,CPT/Product,Service Date,Charges,Historical Posted Payments,Bill ID,Activity Type,Claim ID,Patient MRN,Payer,Primary Provider',
            'Jamie,Doe,"Doe, Jamie",99490,2026-07-01,250,35,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
            'Jamie,Doe,"Doe, Jamie",99439,2026-07-01,175,20,BILL-1,Charge,TC-1001,MRN-1,Medicare,"Smith, Alex"',
        ]));
    }
}
