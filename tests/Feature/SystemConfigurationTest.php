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

class SystemConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_seeds_existing_claim_configuration_as_system_options(): void
    {
        $this->assertSame(8, ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::WORK_STATUS)
            ->whereNull('added_by')
            ->count());
        $this->assertSame(8, ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::WORK_STATUS)
            ->whereNotNull('color')
            ->distinct('color')
            ->count('color'));
        $this->assertSame(2, ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_REASON)
            ->whereNull('added_by')
            ->count());
        $this->assertSame(2, ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->whereIn('value', ['yes', 'no'])
            ->whereNull('added_by')
            ->count());
    }

    public function test_only_admins_can_open_and_manage_system_configuration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/system-configuration')
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => 'Needs Review',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_confirm_delete_an_option(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::DENIAL_REASON,
                'label' => 'Missing Authorization',
            ])
            ->assertRedirect();

        $option = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::DENIAL_REASON)
            ->where('value', 'Missing Authorization')
            ->firstOrFail();
        $this->assertSame($admin->id, $option->added_by);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/system-configuration/{$option->id}", [
                'label' => 'Authorization Missing',
            ])
            ->assertRedirect();
        $this->assertSame('Authorization Missing', $option->fresh()->label);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->delete("/system-configuration/{$option->id}", [
                'confirmation' => 'delete',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('claim_configuration_options', ['id' => $option->id]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->delete("/system-configuration/{$option->id}", [
                'confirmation' => 'confirm',
            ])
            ->assertRedirect();
        $this->assertDatabaseMissing('claim_configuration_options', ['id' => $option->id]);
    }

    public function test_work_status_requires_an_available_unique_color(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => 'Needs Review',
            ])
            ->assertSessionHasErrors('color');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => 'Needs Review',
                'color' => '#f3f4f6',
            ])
            ->assertSessionHasErrors('color');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => 'Needs Review',
                'color' => '#ABC',
            ])
            ->assertSessionHasErrors('color');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => 'Needs Review',
                'color' => '#12ab34',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('claim_configuration_options', [
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::WORK_STATUS,
            'value' => 'needs_review',
            'color' => '#12AB34',
            'added_by' => $admin->id,
        ]);
    }

    public function test_default_draft_work_status_cannot_be_updated_or_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $draft = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::WORK_STATUS)
            ->where('value', 'draft')
            ->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->patch("/system-configuration/{$draft->id}", [
                'label' => 'Renamed Draft',
                'color' => '#F3F4F6',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('option');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->delete("/system-configuration/{$draft->id}", [
                'confirmation' => 'confirm',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('option');

        $draft->refresh();
        $this->assertSame('Draft', $draft->label);
        $this->assertSame('#F3F4F6', $draft->color);
    }

    public function test_credit_status_labels_can_be_edited_but_required_values_cannot_be_added_or_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $yes = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->where('value', 'yes')
            ->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/system-configuration/{$yes->id}", [
                'label' => 'Approved',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('Approved', $yes->fresh()->label);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::CREDIT_STATUS,
                'label' => 'Maybe',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('option_type');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->delete("/system-configuration/{$yes->id}", [
                'confirmation' => 'confirm',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('option');

        $this->assertDatabaseHas('claim_configuration_options', [
            'id' => $yes->id,
            'value' => 'yes',
            'label' => 'Approved',
        ]);
    }

    public function test_claims_use_account_configuration_for_edit_options_and_validation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->where('value', 'yes')
            ->update(['label' => 'Approved']);
        $workStatus = $this->createOption($admin, ClaimConfigurationService::WORK_STATUS, 'quality_review', 'Quality Review');
        $creditReason = $this->createOption($admin, ClaimConfigurationService::CREDIT_REASON, 'contractual_credit', 'Contractual Credit');
        $denialReason = $this->createOption($admin, ClaimConfigurationService::DENIAL_REASON, 'missing_referral', 'Missing Referral');
        $claim = Claim::create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'CONFIG-CLAIM-1',
            'bill_id' => 'CONFIG-CLAIM-1',
            'patient_name' => 'Configured Claim',
            'procedure_code' => '99490',
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('workStatuses', fn ($options) => collect($options)->contains(
                    fn ($option): bool => $option['value'] === $workStatus->value && $option['color'] === '#FFEDD5',
                ))
                ->where('creditStatuses', fn ($options) => collect($options)->contains(
                    fn ($option): bool => $option['value'] === 'yes' && $option['label'] === 'Approved',
                ))
                ->where('creditReasons', fn ($options) => collect($options)->contains('value', $creditReason->value))
                ->where('denialReasons', fn ($options) => collect($options)->contains('value', $denialReason->value)));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/claims/{$claim->id}", [
                'work_status' => $workStatus->value,
                'denial_reason' => $denialReason->value,
                'credit_status' => true,
                'credit_status_date' => '2026-07-30',
                'credit_reason' => $creditReason->value,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $claim->refresh();
        $this->assertSame('quality_review', $claim->work_status);
        $this->assertSame('missing_referral', $claim->denial_reason);
        $this->assertSame('contractual_credit', $claim->credit_reason);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('claims.data.0.lines.0.credit_status_label', 'Approved'));
    }

    public function test_imported_denial_labels_reuse_an_existing_configured_option(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->createOption($admin, ClaimConfigurationService::DENIAL_REASON, 'missing_referral', 'Missing Referral');
        $configurations = app(ClaimConfigurationService::class);

        $resolved = $configurations->resolveDenialReason(AccountType::Tricity->value, 'missing referral');

        $this->assertSame('missing_referral', $resolved);
        $this->assertSame(1, ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::DENIAL_REASON)
            ->count());
    }

    private function createOption(User $admin, string $type, string $value, string $label): ClaimConfigurationOption
    {
        return ClaimConfigurationOption::query()->create([
            'account_type' => AccountType::Tricity->value,
            'option_type' => $type,
            'value' => $value,
            'label' => $label,
            'color' => $type === ClaimConfigurationService::WORK_STATUS ? '#FFEDD5' : null,
            'sort_order' => 100,
            'added_by' => $admin->id,
        ]);
    }
}
