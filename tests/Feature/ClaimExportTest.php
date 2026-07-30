<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimExport;
use App\Models\ClaimImport;
use App\Models\GroupMember;
use App\Models\User;
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

        $this->claim([
            'external_id' => 'TC-EXPORT-1',
            'patient_name' => '=HYPERLINK("https://example.test")',
            'procedure_code' => '99490',
            'work_status' => 'paid',
            'assigned_to' => $agent->id,
            'true_charge' => 185,
        ]);
        $this->claim([
            'external_id' => 'TC-EXPORT-1',
            'procedure_code' => '99439',
            'work_status' => 'draft',
            'true_charge' => 140,
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
            ->assertJsonPath('export.total_rows', 2)
            ->assertJsonPath('export.processed_rows', 2);

        $export = ClaimExport::query()->firstOrFail();
        Storage::assertExists($export->file_path);
        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim(Storage::get($export->file_path))));

        $this->assertCount(3, $rows);
        $this->assertSame('Bill ID', $rows[0][0]);
        $this->assertSame(['99490', '99439'], array_column(array_slice($rows, 1), 8));
        $this->assertSame('\'=HYPERLINK("https://example.test")', $rows[1][2]);
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
