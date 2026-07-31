<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\SystemClaimConfiguration;
use App\Models\Claim;
use App\Models\ClaimConfigurationOption;
use App\Models\ClaimConfigurationSystemDefault;
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

    public function test_colored_configuration_types_require_an_available_unique_color(): void
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

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
                'label' => 'Review Needed',
            ])
            ->assertSessionHasErrors('color');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
                'label' => 'Review Needed',
                'color' => '#dbeafe',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('claim_configuration_options', [
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::MODMED_CLAIM_STATUS,
            'value' => 'review_needed',
            'label' => 'Review Needed',
            'color' => '#DBEAFE',
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
        $modMedStatus = $this->createOption($admin, ClaimConfigurationService::MODMED_CLAIM_STATUS, 'review_needed', 'Review Needed');
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
                ->where('modMedClaimStatuses', fn ($options) => collect($options)->contains(
                    fn ($option): bool => $option['value'] === $modMedStatus->value && $option['color'] === '#DBEAFE',
                ))
                ->where('creditStatuses', fn ($options) => collect($options)->contains(
                    fn ($option): bool => $option['value'] === 'yes' && $option['label'] === 'Approved',
                ))
                ->where('creditReasons', fn ($options) => collect($options)->contains('value', $creditReason->value))
                ->where('denialReasons', fn ($options) => collect($options)->contains('value', $denialReason->value)));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->getJson('/claims/options?filter=modmed_claim_status')
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'review_needed',
                'name' => 'Review Needed',
            ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/claims/{$claim->id}", [
                'work_status' => $workStatus->value,
                'modmed_claim_status' => $modMedStatus->value,
                'denial_reason' => $denialReason->value,
                'credit_status' => true,
                'credit_status_date' => '2026-07-30',
                'credit_reason' => $creditReason->value,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $claim->refresh();
        $this->assertSame('quality_review', $claim->work_status);
        $this->assertSame($workStatus->id, $claim->work_status_id);
        $this->assertSame('review_needed', $claim->modmed_claim_status);
        $this->assertSame($modMedStatus->id, $claim->modmed_claim_status_id);
        $this->assertTrue($claim->modmed_claim_status_manually_set);
        $this->assertSame('missing_referral', $claim->denial_reason);
        $this->assertSame($denialReason->id, $claim->denial_reason_id);
        $this->assertSame('contractual_credit', $claim->credit_reason);
        $this->assertSame($creditReason->id, $claim->credit_reason_id);
        $this->assertSame(
            ClaimConfigurationOption::query()
                ->where('account_type', AccountType::Tricity->value)
                ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
                ->where('value', 'yes')
                ->value('id'),
            $claim->credit_status_id,
        );

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('claims.data.0.lines.0.modmed_claim_status_label', 'Review Needed')
                ->where('claims.data.0.lines.0.modmed_claim_status_color', '#DBEAFE')
                ->where('claims.data.0.lines.0.credit_status_label', 'Approved'));
    }

    public function test_configuration_renames_propagate_to_every_linked_claim_field(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $workStatus = $this->createOption($admin, ClaimConfigurationService::WORK_STATUS, 'ready_to_bill', 'Ready to Bill');
        $modMedStatus = $this->createOption($admin, ClaimConfigurationService::MODMED_CLAIM_STATUS, 'queued_review', 'Queued Review');
        $creditReason = $this->createOption($admin, ClaimConfigurationService::CREDIT_REASON, 'billing_credit', 'Billing Credit');
        $denialReason = $this->createOption($admin, ClaimConfigurationService::DENIAL_REASON, 'coding_issue', 'Coding Issue');
        $creditStatus = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->where('value', 'yes')
            ->firstOrFail();
        $claim = Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'CONFIG-RENAME-1',
            'bill_id' => 'CONFIG-RENAME-1',
            'patient_name' => 'Rename Test',
            'procedure_code' => '99490',
            'work_status' => $workStatus->value,
            'modmed_claim_status' => $modMedStatus->value,
            'credit_status' => true,
            'credit_reason' => $creditReason->value,
            'denial_reason' => $denialReason->value,
        ]);

        $this->assertSame($workStatus->id, $claim->work_status_id);
        $this->assertSame($modMedStatus->id, $claim->modmed_claim_status_id);
        $this->assertSame($creditStatus->id, $claim->credit_status_id);
        $this->assertSame($creditReason->id, $claim->credit_reason_id);
        $this->assertSame($denialReason->id, $claim->denial_reason_id);

        foreach ([
            [$workStatus, 'Paid V2', '#FFEDD5'],
            [$modMedStatus, 'Review V2', '#DBEAFE'],
            [$creditStatus, 'Credited V2', null],
            [$creditReason, 'Credit Reason V2', null],
            [$denialReason, 'Denial Reason V2', null],
        ] as [$option, $label, $color]) {
            $this->actingAs($admin)
                ->withSession(['account_type' => AccountType::Tricity->value])
                ->patch("/system-configuration/{$option->id}", array_filter([
                    'label' => $label,
                    'color' => $color,
                ], fn ($value): bool => $value !== null))
                ->assertRedirect()
                ->assertSessionDoesntHaveErrors();
        }

        $claim->refresh();
        $this->assertSame($workStatus->id, $claim->work_status_id);
        $this->assertSame($modMedStatus->id, $claim->modmed_claim_status_id);
        $this->assertSame($creditStatus->id, $claim->credit_status_id);
        $this->assertSame($creditReason->id, $claim->credit_reason_id);
        $this->assertSame($denialReason->id, $claim->denial_reason_id);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('claims.data.0.lines.0.work_status_label', 'Paid V2')
                ->where('claims.data.0.lines.0.modmed_claim_status_label', 'Review V2')
                ->where('claims.data.0.lines.0.credit_status_label', 'Credited V2')
                ->where('claims.data.0.lines.0.credit_reason_label', 'Credit Reason V2')
                ->where('claims.data.0.lines.0.denial_reason_label', 'Denial Reason V2'));

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->delete("/system-configuration/{$workStatus->id}", ['confirmation' => 'confirm'])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('option');
    }

    public function test_admin_can_restore_system_work_status_defaults_without_changing_custom_options_or_claim_references(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $paid = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::WORK_STATUS)
            ->where('value', SystemClaimConfiguration::WorkPaid->internalValue())
            ->firstOrFail();
        $rebilled = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::WORK_STATUS)
            ->where('value', SystemClaimConfiguration::WorkRebilled->internalValue())
            ->firstOrFail();
        $claim = Claim::query()->create([
            'account_type' => AccountType::Tricity->value,
            'external_id' => 'RESTORE-WORK-STATUS-1',
            'bill_id' => 'RESTORE-WORK-STATUS-1',
            'patient_name' => 'Restore Test',
            'procedure_code' => '99490',
            'work_status' => SystemClaimConfiguration::WorkPaid->internalValue(),
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->patch("/system-configuration/{$paid->id}", [
                'label' => 'Paid V2',
                'color' => '#ABCDEF',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
        $paid->update(['value' => 'paid_v2_internal']);
        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->delete("/system-configuration/{$rebilled->id}", ['confirmation' => 'confirm'])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $custom = $this->createOption($admin, ClaimConfigurationService::WORK_STATUS, 'quality_review', 'Quality Review');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration/work_status/restore-defaults', ['confirmation' => 'restore'])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $paid->refresh();
        $claim->refresh();
        $custom->refresh();
        $this->assertSame(SystemClaimConfiguration::WorkPaid->value, $paid->system_key);
        $this->assertSame(SystemClaimConfiguration::WorkPaid->internalValue(), $paid->value);
        $this->assertSame(SystemClaimConfiguration::WorkPaid->label(), $paid->label);
        $this->assertSame(SystemClaimConfiguration::WorkPaid->color(), $paid->color);
        $this->assertSame(SystemClaimConfiguration::WorkPaid->sortOrder(), $paid->sort_order);
        $this->assertNull($paid->added_by);
        $this->assertSame($paid->id, $claim->work_status_id);
        $this->assertSame('Quality Review', $custom->label);
        $this->assertSame('#FFEDD5', $custom->color);
        $this->assertSame($admin->id, $custom->added_by);
        $this->assertDatabaseHas('claim_configuration_options', [
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::WORK_STATUS,
            'system_key' => SystemClaimConfiguration::WorkRebilled->value,
            'value' => SystemClaimConfiguration::WorkRebilled->internalValue(),
            'label' => SystemClaimConfiguration::WorkRebilled->label(),
            'color' => SystemClaimConfiguration::WorkRebilled->color(),
            'sort_order' => SystemClaimConfiguration::WorkRebilled->sortOrder(),
            'added_by' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/claims')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('claims.data.0.lines.0.work_status_label', SystemClaimConfiguration::WorkPaid->label()));
    }

    public function test_system_work_status_names_and_colors_remain_reserved_after_a_default_is_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $rebilled = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::WORK_STATUS)
            ->where('value', SystemClaimConfiguration::WorkRebilled->internalValue())
            ->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->delete("/system-configuration/{$rebilled->id}", ['confirmation' => 'confirm'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => SystemClaimConfiguration::WorkRebilled->label(),
                'color' => '#123456',
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('label');

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->from('/system-configuration')
            ->post('/system-configuration', [
                'option_type' => ClaimConfigurationService::WORK_STATUS,
                'label' => 'Custom Blue',
                'color' => SystemClaimConfiguration::WorkRebilled->color(),
            ])
            ->assertRedirect('/system-configuration')
            ->assertSessionHasErrors('color');
    }

    public function test_admin_can_restore_defaults_for_every_system_controlled_configuration_type(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $configurations = app(ClaimConfigurationService::class);

        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->get('/system-configuration')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sections.0.can_restore_defaults', true)
                ->where('sections.1.can_restore_defaults', true)
                ->where('sections.2.can_restore_defaults', true)
                ->where('sections.3.can_restore_defaults', true)
                ->where('sections.4.type', ClaimConfigurationService::DENIAL_REASON)
                ->where('sections.4.can_restore_defaults', false));

        $modMed = $configurations->resolveModMedClaimStatusOption(AccountType::Tricity->value, 'System Review')->refresh();
        $denial = $configurations->resolveDenialReasonOption(AccountType::Tricity->value, 'System Denial')->refresh();
        $creditStatus = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_STATUS)
            ->where('value', SystemClaimConfiguration::CreditStatusYes->internalValue())
            ->firstOrFail();
        $creditReason = ClaimConfigurationOption::query()
            ->where('account_type', AccountType::Tricity->value)
            ->where('option_type', ClaimConfigurationService::CREDIT_REASON)
            ->where('value', SystemClaimConfiguration::CreditReasonInactiveInsurance->internalValue())
            ->firstOrFail();
        $customModMed = $this->createOption($admin, ClaimConfigurationService::MODMED_CLAIM_STATUS, 'custom_modmed', 'Custom ModMed');
        $customDenial = $this->createOption($admin, ClaimConfigurationService::DENIAL_REASON, 'custom_denial', 'Custom Denial');
        $customCreditReason = $this->createOption($admin, ClaimConfigurationService::CREDIT_REASON, 'custom_credit', 'Custom Credit');

        foreach ([
            ClaimConfigurationService::MODMED_CLAIM_STATUS => $modMed,
            ClaimConfigurationService::CREDIT_STATUS => $creditStatus,
            ClaimConfigurationService::CREDIT_REASON => $creditReason,
        ] as $type => $option) {
            $default = ClaimConfigurationSystemDefault::query()
                ->where('account_type', AccountType::Tricity->value)
                ->where('option_type', $type)
                ->where('system_key', $option->system_key)
                ->firstOrFail();
            $option->delete();

            $this->actingAs($admin)
                ->withSession(['account_type' => AccountType::Tricity->value])
                ->post("/system-configuration/{$type}/restore-defaults", ['confirmation' => 'restore'])
                ->assertRedirect()
                ->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('claim_configuration_options', [
                'account_type' => AccountType::Tricity->value,
                'option_type' => $type,
                'system_key' => $default->system_key,
                'value' => $default->value,
                'label' => $default->label,
                'color' => $default->color,
                'sort_order' => $default->sort_order,
                'added_by' => null,
            ]);
        }

        foreach ([$customModMed, $customDenial, $customCreditReason] as $custom) {
            $this->assertDatabaseHas('claim_configuration_options', [
                'id' => $custom->id,
                'value' => $custom->value,
                'label' => $custom->label,
                'added_by' => $admin->id,
            ]);
        }

        $this->assertNull($denial->system_key);
        $this->assertDatabaseMissing('claim_configuration_system_defaults', [
            'account_type' => AccountType::Tricity->value,
            'option_type' => ClaimConfigurationService::DENIAL_REASON,
        ]);
        $this->actingAs($admin)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration/denial_reason/restore-defaults', ['confirmation' => 'restore'])
            ->assertNotFound();
    }

    public function test_only_admins_can_restore_system_work_status_defaults(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['account_type' => AccountType::Tricity->value])
            ->post('/system-configuration/work_status/restore-defaults', ['confirmation' => 'restore'])
            ->assertForbidden();
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
            'color' => match ($type) {
                ClaimConfigurationService::WORK_STATUS => '#FFEDD5',
                ClaimConfigurationService::MODMED_CLAIM_STATUS => '#DBEAFE',
                default => null,
            },
            'sort_order' => 100,
            'added_by' => $admin->id,
        ]);
    }
}
