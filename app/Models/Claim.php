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

    protected $fillable = [
        'account_type', 'external_id', 'patient_name', 'date_of_service', 'payer',
        'provider', 'cpt_code', 'billed_amount', 'balance', 'status', 'priority',
        'assigned_to', 'notes', 'last_import_id', 'source_hash', 'uid', 'bill_id',
        'payer_name', 'rendering_provider', 'primary_provider', 'payments', 'new_payments',
        'true_balance', 'true_charge', 'adjustments', 'aging_days', 'denial_reason', 'claim_status',
        'modmed_claim_status', 'cf_invoice_date', 'cf_invoice_amount',
        'work_status', 'work_status_manually_set', 'claimed_amount', 'diagnosis_code',
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
            if ($claim->account_type !== AccountType::Tricity->value) {
                return;
            }

            if (blank($claim->bill_id) && filled($claim->external_id)) {
                $claim->bill_id = $claim->external_id;
            }

            if (blank($claim->external_id) && filled($claim->bill_id)) {
                $claim->external_id = $claim->bill_id;
            }
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
}
