<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\ClaimExport;
use App\Models\ClaimImport;
use App\Models\ClaimImportSnapshot;
use App\Models\ClaimRawRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResetClaimsImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_tricity_reset_removes_only_tricity_claim_data_and_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $tricityImport = ClaimImport::query()->create([
            'account_type' => AccountType::Tricity->value,
            'file_name' => 'tricity.csv',
            'stored_path' => 'claim-imports/tricity.csv',
            'status' => 'completed',
            'imported_by' => $user->id,
        ]);
        $principleImport = ClaimImport::query()->create([
            'account_type' => AccountType::Principle->value,
            'file_name' => 'principle.csv',
            'stored_path' => 'claim-imports/principle.csv',
            'status' => 'completed',
            'imported_by' => $user->id,
        ]);
        $tricityClaim = Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TRICITY-RESET-1',
            'patient_name' => 'Tricity Patient',
            'last_import_id' => $tricityImport->id,
        ]);
        $principleClaim = Claim::query()->create([
            'account_type' => AccountType::Principle->value,
            'external_id' => 'PRINCIPLE-KEEP-1',
            'patient_name' => 'Principle Patient',
            'last_import_id' => $principleImport->id,
        ]);

        ClaimRawRow::query()->create([
            'claim_id' => $tricityClaim->id,
            'claim_import_id' => $tricityImport->id,
            'raw_payload' => ['bill_id' => 'TRICITY-RESET-1'],
        ]);
        ClaimRawRow::query()->create([
            'claim_id' => $principleClaim->id,
            'claim_import_id' => $principleImport->id,
            'raw_payload' => ['claim_id' => 'PRINCIPLE-KEEP-1'],
        ]);
        ClaimImportSnapshot::query()->create([
            'claim_import_id' => $tricityImport->id,
            'claim_id' => $tricityClaim->id,
            'snapshot_data' => ['claim' => $tricityClaim->toArray()],
        ]);
        ClaimActivity::query()->create([
            'account_type' => AccountType::Tricity->value,
            'claim_id' => $tricityClaim->id,
            'user_id' => $user->id,
            'action' => 'claim_updated',
            'description' => 'Tricity activity',
        ]);
        ClaimActivity::query()->create([
            'account_type' => AccountType::Principle->value,
            'claim_id' => $principleClaim->id,
            'user_id' => $user->id,
            'action' => 'claim_updated',
            'description' => 'Principle activity',
        ]);
        $tricityExport = ClaimExport::query()->create([
            'account_type' => AccountType::Tricity->value,
            'user_id' => $user->id,
            'file_name' => 'tricity-export.csv',
            'file_path' => 'claim-exports/tricity-pain-associates/tricity-export.csv',
            'status' => 'completed',
        ]);
        $principleExport = ClaimExport::query()->create([
            'account_type' => AccountType::Principle->value,
            'user_id' => $user->id,
            'file_name' => 'principle-export.csv',
            'file_path' => 'claim-exports/principle-spine-and-pain/principle-export.csv',
            'status' => 'completed',
        ]);

        foreach ([
            $tricityImport->stored_path,
            $principleImport->stored_path,
            $tricityExport->file_path,
            $principleExport->file_path,
        ] as $path) {
            Storage::put($path, 'test');
        }

        $this->artisan('reset-claims:import', ['--tricity' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('claims', ['id' => $tricityClaim->id]);
        $this->assertDatabaseMissing('claim_imports', ['id' => $tricityImport->id]);
        $this->assertDatabaseMissing('claim_exports', ['id' => $tricityExport->id]);
        $this->assertDatabaseMissing('claim_activities', ['account_type' => AccountType::Tricity->value]);
        $this->assertDatabaseMissing('claim_raw_rows', ['claim_id' => $tricityClaim->id]);
        $this->assertDatabaseMissing('claim_import_snapshots', ['claim_import_id' => $tricityImport->id]);
        Storage::assertMissing($tricityImport->stored_path);
        Storage::assertMissing($tricityExport->file_path);

        $this->assertDatabaseHas('claims', ['id' => $principleClaim->id]);
        $this->assertDatabaseHas('claim_imports', ['id' => $principleImport->id]);
        $this->assertDatabaseHas('claim_exports', ['id' => $principleExport->id]);
        $this->assertDatabaseHas('claim_activities', ['account_type' => AccountType::Principle->value]);
        $this->assertDatabaseHas('claim_raw_rows', ['claim_id' => $principleClaim->id]);
        Storage::assertExists($principleImport->stored_path);
        Storage::assertExists($principleExport->file_path);
    }
}
