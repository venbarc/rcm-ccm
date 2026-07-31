<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Claim extends Model
{
    use HasFactory;

    protected $attributes = [
        'work_status' => 'draft',
    ];

    protected $fillable = [
        'account_type', 'external_id', 'patient_name', 'date_of_service', 'payer',
        'provider', 'cpt_code', 'billed_amount', 'balance', 'status', 'priority',
        'assigned_to', 'notes', 'last_import_id', 'source_hash', 'uid', 'bill_id',
        'payer_name', 'rendering_provider', 'primary_provider', 'payments', 'new_payments',
        'true_balance', 'true_charge', 'adjustments', 'aging_days', 'denial_reason', 'denial_reason_id', 'claim_status',
        'modmed_claim_status', 'modmed_claim_status_id', 'modmed_claim_status_manually_set', 'cf_invoice_date', 'cf_invoice_amount', 'invoiced_status',
        'invoiced_status_date', 'credit_status', 'credit_status_id', 'credit_status_date', 'credit_reason', 'credit_reason_id',
        'work_status', 'work_status_id', 'work_status_manually_set', 'claimed_amount', 'diagnosis_code',
        'first_name', 'last_name', 'modifiers', 'patient_dob', 'patient_id',
        'payer_category', 'procedure_code', 'service_type', 'service_date_start',
        'service_date_end', 'subscriber_id', 'units', 'activity_type', 'batch_user',
        'batch_name', 'code_category', 'coverage_type', 'division', 'financial_category',
        'location', 'source_notes', 'ordering_provider', 'package_name',
        'place_of_service_code', 'posted_date', 'practice_location', 'primary_biller',
        'primary_biller_role', 'primary_modifier', 'primary_provider_role', 'quick_code',
        'recorded_by', 'supervising_provider', 'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_service' => 'date:Y-m-d',
            'billed_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'payments' => 'decimal:2',
            'new_payments' => 'decimal:2',
            'true_balance' => 'decimal:2',
            'true_charge' => 'decimal:2',
            'adjustments' => 'decimal:2',
            'claimed_amount' => 'decimal:2',
            'units' => 'decimal:2',
            'patient_dob' => 'date:Y-m-d',
            'cf_invoice_date' => 'date:Y-m-d',
            'cf_invoice_amount' => 'decimal:2',
            'work_status_id' => 'integer',
            'modmed_claim_status_id' => 'integer',
            'credit_status_id' => 'integer',
            'credit_reason_id' => 'integer',
            'denial_reason_id' => 'integer',
            'modmed_claim_status_manually_set' => 'boolean',
            'invoiced_status_date' => 'date:Y-m-d',
            'credit_status' => 'boolean',
            'credit_status_date' => 'date:Y-m-d',
            'service_date_start' => 'date:Y-m-d',
            'service_date_end' => 'date:Y-m-d',
            'posted_date' => 'date:Y-m-d',
            'transaction_date' => 'date:Y-m-d',
            'work_status_manually_set' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Claim $claim): void {
            $claim->syncConfigurationReferences();

            if ($claim->account_type !== AccountType::Tricity->value) {
                return;
            }

            if (blank($claim->bill_id) && filled($claim->external_id)) {
                $claim->bill_id = $claim->external_id;
            }

            if (blank($claim->external_id) && filled($claim->bill_id)) {
                $claim->external_id = $claim->bill_id;
            }

            $claim->invoiced_status = 'invoiced';
            $claim->invoiced_status_date = $claim->cf_invoice_date;
        });
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClaimActivity::class);
    }

    public function rawRow(): HasOne
    {
        return $this->hasOne(ClaimRawRow::class);
    }

    public function workStatusOption(): BelongsTo
    {
        return $this->belongsTo(ClaimConfigurationOption::class, 'work_status_id');
    }

    public function modMedClaimStatusOption(): BelongsTo
    {
        return $this->belongsTo(ClaimConfigurationOption::class, 'modmed_claim_status_id');
    }

    public function creditStatusOption(): BelongsTo
    {
        return $this->belongsTo(ClaimConfigurationOption::class, 'credit_status_id');
    }

    public function creditReasonOption(): BelongsTo
    {
        return $this->belongsTo(ClaimConfigurationOption::class, 'credit_reason_id');
    }

    public function denialReasonOption(): BelongsTo
    {
        return $this->belongsTo(ClaimConfigurationOption::class, 'denial_reason_id');
    }

    private function syncConfigurationReferences(): void
    {
        $this->syncConfigurationReference('work_status', 'work_status_id', 'work_status', 'draft');
        $this->syncConfigurationReference('modmed_claim_status', 'modmed_claim_status_id', 'modmed_claim_status');
        $this->syncCreditStatusReference();
        $this->syncConfigurationReference('credit_reason', 'credit_reason_id', 'credit_reason');
        $this->syncConfigurationReference('denial_reason', 'denial_reason_id', 'denial_reason');
    }

    private function syncConfigurationReference(
        string $valueField,
        string $idField,
        string $optionType,
        ?string $defaultValue = null,
    ): void {
        if ($this->isDirty($idField) && $this->isDirty($valueField)) {
            if ($this->getAttribute($idField) === null && $defaultValue === null) {
                $this->setAttribute($valueField, null);
            }

            return;
        }

        if ($this->isDirty($idField)) {
            $option = $this->configurationOptionById($this->getAttribute($idField), $optionType);
            if ($option) {
                $this->setAttribute($valueField, $option->value);

                return;
            }

            if ($this->getAttribute($idField) === null && $defaultValue === null) {
                $this->setAttribute($valueField, null);

                return;
            }
        }

        $value = trim((string) $this->getAttribute($valueField));
        $value = $value !== '' ? $value : $defaultValue;
        if ($value === null) {
            $this->setAttribute($valueField, null);
            $this->setAttribute($idField, null);

            return;
        }

        if ($this->isDirty($valueField) || ! $this->exists || $this->getAttribute($idField) === null) {
            $option = $this->configurationOptionByValue($value, $optionType);
            if ($option) {
                $this->setAttribute($valueField, $option->value);
                $this->setAttribute($idField, $option->id);
            }
        }
    }

    private function syncCreditStatusReference(): void
    {
        if ($this->isDirty('credit_status_id') && $this->isDirty('credit_status')) {
            return;
        }

        if ($this->isDirty('credit_status_id')) {
            $option = $this->configurationOptionById($this->credit_status_id, 'credit_status');
            if ($option) {
                $this->credit_status = $option->value === 'yes';

                return;
            }

            if ($this->credit_status_id === null) {
                $this->credit_status = null;

                return;
            }
        }

        if ($this->credit_status === null) {
            $this->credit_status_id = null;

            return;
        }

        if ($this->isDirty('credit_status') || ! $this->exists || $this->credit_status_id === null) {
            $option = $this->configurationOptionByValue($this->credit_status ? 'yes' : 'no', 'credit_status');
            if ($option) {
                $this->credit_status_id = $option->id;
            }
        }
    }

    private function configurationOptionById(mixed $id, string $type): ?ClaimConfigurationOption
    {
        if (! is_numeric($id)) {
            return null;
        }

        return ClaimConfigurationOption::query()
            ->whereKey((int) $id)
            ->where('account_type', $this->account_type)
            ->where('option_type', $type)
            ->first();
    }

    private function configurationOptionByValue(string $value, string $type): ?ClaimConfigurationOption
    {
        return ClaimConfigurationOption::query()
            ->where('account_type', $this->account_type)
            ->where('option_type', $type)
            ->where(function ($query) use ($value): void {
                $query->where('value', $value)
                    ->orWhereRaw('LOWER(label) = ?', [mb_strtolower($value)]);
            })
            ->first();
    }
}
