<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimExport;
use App\Models\User;
use App\Services\ClaimConfigurationService;
use App\Services\ClaimImportService;
use App\Support\AccountContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrincipleWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'local', 'queue.default' => 'sync']);
        Storage::fake('local');
    }

    public function test_authorized_user_can_switch_to_the_principle_workspace(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/claims')
            ->post('/account-type/switch', ['account_type' => AccountType::Principle->value])
            ->assertRedirect('/claims')
            ->assertSessionHas('account_type', AccountType::Principle->value);

        foreach (['/dashboard', '/claims', '/activity-logs'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSessionHas('account_type', AccountType::Principle->value)
                ->assertInertia(fn (Assert $page) => $page
                    ->where('activeAccount', AccountType::Principle->value));
        }

        $this->from('/activity-logs')
            ->post('/account-type/switch', ['account_type' => AccountType::Tricity->value])
            ->assertRedirect('/activity-logs')
            ->assertSessionHas('account_type', AccountType::Tricity->value);

        $this->get('/claims')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeAccount', AccountType::Tricity->value));
    }

    public function test_principle_claims_are_physically_isolated_from_tricity_claims(): void
    {
        AccountContext::runWith(AccountType::Tricity->value, fn () => Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'TRICITY-ONLY',
            'patient_name' => 'Tricity Patient',
        ]));
        AccountContext::runWith(AccountType::Principle->value, fn () => Claim::query()->create([
            'account_type' => AccountType::Principle->value,
            'external_id' => 'PRINCIPLE-ONLY',
            'patient_name' => 'Principle Patient',
        ]));

        $this->assertTrue(DB::table('claims')->where('external_id', 'TRICITY-ONLY')->exists());
        $this->assertFalse(DB::table('claims')->where('external_id', 'PRINCIPLE-ONLY')->exists());
        $this->assertTrue(DB::table('principle_claims')->where('external_id', 'PRINCIPLE-ONLY')->exists());
        $this->assertFalse(DB::table('principle_claims')->where('external_id', 'TRICITY-ONLY')->exists());

        $this->assertSame(1, AccountContext::runWith(AccountType::Tricity->value, fn (): int => Claim::query()->count()));
        $this->assertSame(1, AccountContext::runWith(AccountType::Principle->value, fn (): int => Claim::query()->count()));
    }

    public function test_principle_source_column_migration_is_safe_when_cloned_columns_already_exist(): void
    {
        $migration = require database_path('migrations/2026_08_10_000002_add_principle_source_columns_to_principle_claims.php');

        $migration->up();

        foreach ([
            'primary_claim_id',
            'location_name',
            'patient_date_of_birth',
            'chart_number',
            'responsible_payer',
            'charge_amount',
            'total_payment',
            'insurance_balance',
            'patient_balance',
            'total_balance',
            'claim_cpt',
            'true_charge_per_unit',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('principle_claims', $column));
        }

        $this->assertTrue(Schema::hasIndex('principle_claims', ['primary_claim_id']));
        $this->assertTrue(Schema::hasIndex('principle_claims', ['claim_cpt']));
    }

    public function test_principle_uses_the_shared_queued_claim_import_workflow(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $file = UploadedFile::fake()->createWithContent('principle.csv', implode("\n", [
            'Procedure Code,Primary Claim ID,True Charge,Patient Name,Chart Number,Patient Date of Birth,Responsible Payer,Rendering Provider,Location Name,Date of Service,Total Payment,Total Balance,claim-cpt',
            '99490,PRINCIPLE-100,100,"Smith, Pat",PAT100,1980-01-02,Example Payer,Example Provider,Example Location,2026-08-01,25,75,PRINCIPLE-100-99490',
        ]));

        $import = app(ClaimImportService::class)->queue(
            $file,
            AccountType::Principle->value,
            $admin,
        );

        $this->assertSame('completed', $import->status);
        $this->assertDatabaseHas('principle_claim_imports', [
            'id' => $import->id,
            'account_type' => AccountType::Principle->value,
        ]);
        $this->assertDatabaseHas('principle_claims', [
            'account_type' => AccountType::Principle->value,
            'bill_id' => 'PRINCIPLE-100',
            'primary_claim_id' => 'PRINCIPLE-100',
            'patient_name' => 'Smith, Pat',
            'chart_number' => 'PAT100',
            'responsible_payer' => 'Example Payer',
            'rendering_provider' => 'Example Provider',
            'total_payment' => 25,
            'total_balance' => 75,
            'true_balance' => null,
        ]);
        $this->assertDatabaseMissing('claims', ['bill_id' => 'PRINCIPLE-100']);
    }

    public function test_real_principle_workbook_imports_confirmed_source_columns_without_deriving_true_balance(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixture = base_path('CSV_SANDBOX/principle/July1Principle CCM Charges all data 8.6.26.xlsx');
        $file = new UploadedFile(
            $fixture,
            basename($fixture),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $import = app(ClaimImportService::class)->queue($file, AccountType::Principle->value, $admin);

        $this->assertSame('completed', $import->status);
        $this->assertSame(419, $import->total_rows);
        $this->assertDatabaseCount('principle_claims', 419);
        $this->assertDatabaseCount('principle_claim_raw_rows', 419);
        $this->assertDatabaseHas('principle_claims', [
            'primary_claim_id' => '567781492',
            'bill_id' => '567781492',
            'procedure_code' => '99439',
            'claim_cpt' => '567781492-99439',
            'rendering_provider' => 'ALCARAZ, ERIC',
            'responsible_payer' => 'Medicare Part B Texas *',
            'location_name' => 'CLEAR LAKE INTERVENTIONAL PAIN SPECIALISTS',
            'chart_number' => 'DANLU001',
            'patient_date_of_birth' => '1958-02-26',
            'date_of_service' => '2026-07-31',
            'charge_amount' => 280,
            'cf_invoice_amount' => 40,
            'total_payment' => 0,
            'insurance_balance' => 280,
            'patient_balance' => 0,
            'total_balance' => 280,
            'true_charge' => 76.8,
            'true_charge_per_unit' => 38.4,
            'true_balance' => null,
            'primary_provider' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->get('/claims?search=567781492&primary_provider=ALCARAZ%2C%20ERIC&payer_name=Medicare%20Part%20B%20Texas%20%2A&procedure_code=99439')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('claims.data')
                ->where('claims.data.0.bill_id', '567781492')
                ->where('claims.data.0.primary_provider', 'ALCARAZ, ERIC')
                ->where('claims.data.0.payer_name', 'Medicare Part B Texas *')
                ->where('claims.data.0.lines.0.claim_cpt', '567781492-99439')
                ->where('summary.totalTrueBalance', fn ($value): bool => (float) $value === 0.0));
    }

    public function test_principle_claims_filter_by_date_of_service_range_and_show_zero_true_balance(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        AccountContext::runWith(AccountType::Principle->value, function (): void {
            foreach ([
                ['id' => 'PRINCIPLE-DOS-JULY', 'date' => '2026-07-31'],
                ['id' => 'PRINCIPLE-DOS-MATCH', 'date' => '2026-08-10'],
                ['id' => 'PRINCIPLE-DOS-LATE', 'date' => '2026-08-20'],
            ] as $claim) {
                Claim::query()->create([
                    'account_type' => AccountType::Principle->value,
                    'external_id' => $claim['id'],
                    'primary_claim_id' => $claim['id'],
                    'patient_name' => 'DOS Filter Patient',
                    'procedure_code' => '99490',
                    'date_of_service' => $claim['date'],
                    'true_charge' => 50,
                    'true_balance' => null,
                ]);
            }
        });

        $query = http_build_query([
            'service_date_from' => '2026-08-01',
            'service_date_to' => '2026-08-15',
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->get("/claims?{$query}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.service_date_from', '2026-08-01')
                ->where('filters.service_date_to', '2026-08-15')
                ->where('claims.total', 1)
                ->where('claims.data.0.bill_id', 'PRINCIPLE-DOS-MATCH')
                ->where('summary.totalCount', 1)
                ->where('summary.totalTrueBalance', fn ($value): bool => (float) $value === 0.0));
    }

    public function test_principle_exposes_every_shared_tricity_workspace_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AccountContext::runWith(AccountType::Principle->value, fn () => Claim::query()->create([
            'account_type' => AccountType::Principle->value,
            'external_id' => 'PRINCIPLE-PAGE-100',
            'patient_name' => 'Principle Page Patient',
            'procedure_code' => '99490',
            'true_balance' => 50,
        ]));
        $this->actingAs($admin)->withSession(['account_type' => AccountType::Principle->value]);

        foreach ([
            '/dashboard',
            '/claims',
            '/claims-import',
            '/activity-logs',
            '/system-configuration',
            '/user-management',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }

        $this->get('/assignments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.claim_groups', 1)
                ->where('summary.claim_lines', 1));
    }

    public function test_principle_omits_modmed_claim_status_from_system_configuration(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->get('/system-configuration')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sections', 4)
                ->where('sections', fn ($sections): bool => ! collect($sections)
                    ->contains('type', ClaimConfigurationService::MODMED_CLAIM_STATUS)));

        $this->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
                'label' => 'Principle-only status',
                'color' => '#ABCDEF',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('option_type');

        $this->post('/system-configuration/modmed_claim_status/restore-defaults', [
            'confirmation' => 'restore',
        ])->assertNotFound();

        $this->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/system-configuration')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sections', 5)
                ->where('sections', fn ($sections): bool => collect($sections)
                    ->contains('type', ClaimConfigurationService::MODMED_CLAIM_STATUS)));
    }

    public function test_principle_invoiced_summary_uses_confirmed_invoice_amount_and_service_date(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        AccountContext::runWith(AccountType::Principle->value, function (): void {
            Claim::query()->create([
                'account_type' => AccountType::Principle->value,
                'external_id' => 'PRINCIPLE-INVOICE-JULY',
                'primary_claim_id' => 'PRINCIPLE-INVOICE-JULY',
                'patient_name' => 'Principle July Patient',
                'procedure_code' => '99490',
                'date_of_service' => '2026-07-15',
                'units' => 2,
                'cf_invoice_amount' => 40,
            ]);
            Claim::query()->create([
                'account_type' => AccountType::Principle->value,
                'external_id' => 'PRINCIPLE-INVOICE-JUNE',
                'primary_claim_id' => 'PRINCIPLE-INVOICE-JUNE',
                'patient_name' => 'Principle June Patient',
                'procedure_code' => '99439',
                'date_of_service' => '2026-06-15',
                'units' => 3,
                'cf_invoice_amount' => 60,
            ]);
            Claim::query()->create([
                'account_type' => AccountType::Principle->value,
                'external_id' => 'PRINCIPLE-NOT-INVOICED',
                'primary_claim_id' => 'PRINCIPLE-NOT-INVOICED',
                'patient_name' => 'Principle Uninvoiced Patient',
                'procedure_code' => '99491',
                'date_of_service' => '2026-07-20',
                'units' => 4,
                'cf_invoice_amount' => null,
            ]);
        });

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->get('/dashboard?invoiced_service_start=2026-07-01&invoiced_service_end=2026-07-31')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('panelFilters.invoicedSummary.invoiceStart', null)
                ->where('panelFilters.invoicedSummary.invoiceEnd', null)
                ->where('panelFilters.invoicedSummary.serviceStart', '2026-07-01')
                ->where('panelFilters.invoicedSummary.serviceEnd', '2026-07-31')
                ->has('invoicedSummary.rows', 1)
                ->where('invoicedSummary.rows.0.cpt', '99490')
                ->where('invoicedSummary.rows.0.units', fn ($value): bool => (float) $value === 2.0)
                ->where('invoicedSummary.totalUnits', fn ($value): bool => (float) $value === 2.0));
    }

    public function test_principle_uses_the_shared_queued_claim_export_workflow(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AccountContext::runWith(AccountType::Principle->value, fn () => Claim::query()->create([
            'account_type' => AccountType::Principle->value,
            'external_id' => 'PRINCIPLE-EXPORT-100',
            'bill_id' => 'PRINCIPLE-EXPORT-100',
            'primary_claim_id' => 'PRINCIPLE-EXPORT-100',
            'patient_name' => 'Principle Export Patient',
            'procedure_code' => '99490',
            'true_charge' => 100,
            'total_payment' => 25,
            'total_balance' => 75,
            'claim_cpt' => 'PRINCIPLE-EXPORT-100-99490',
        ]));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Principle->value])
            ->postJson('/claims-export/start', ['type' => 'all'])
            ->assertAccepted()
            ->assertJsonPath('export.status', 'completed')
            ->assertJsonPath('export.total_rows', 1);

        $this->assertDatabaseHas('principle_claim_exports', [
            'account_type' => AccountType::Principle->value,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('claim_exports', 0);

        $export = AccountContext::runWith(
            AccountType::Principle->value,
            fn (): ClaimExport => ClaimExport::query()->firstOrFail(),
        );
        $rows = array_values(array_filter(preg_split('/\r\n|\r|\n/', Storage::get($export->file_path)) ?: []));
        $headers = str_getcsv($rows[0]);
        $values = str_getcsv($rows[1]);

        $this->assertContains('Primary Claim ID', $headers);
        $this->assertContains('Rendering Provider', $headers);
        $this->assertContains('Total Payment', $headers);
        $this->assertNotContains('Bill ID', $headers);
        $this->assertNotContains('True Balance', $headers);
        $this->assertNotContains('Payer-CPT', $headers);
        $this->assertSame('PRINCIPLE-EXPORT-100', $values[array_search('Primary Claim ID', $headers, true)]);
        $this->assertSame('25.00', $values[array_search('Total Payment', $headers, true)]);
    }
}
