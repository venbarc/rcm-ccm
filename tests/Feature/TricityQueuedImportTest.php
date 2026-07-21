<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimRawRow;
use App\Models\User;
use App\Services\ClaimImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TricityQueuedImportTest extends TestCase
{
    use RefreshDatabase;

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

        $secondImport = $imports->queue($this->tricityFile(), AccountType::Tricity->value, $user)->fresh();
        $claim->refresh();

        $this->assertSame(0, $secondImport->created_count);
        $this->assertSame(2, $secondImport->updated_count);
        $this->assertSame('appeal', $claim->work_status);
        $this->assertSame('Missing authorization', $claim->denial_reason);
        $this->assertSame('Appeal submitted.', $claim->notes);
        $this->assertNull($claim->true_balance);
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
}
