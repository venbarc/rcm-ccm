<?php

namespace App\Support;

use App\Enums\AccountType;

final class ClaimWorkspace
{
    public const PRINCIPLE_DEFAULT_INVOICE_DATE = '2026-07-31';

    /** @var array<string, string> */
    private const PRINCIPLE_FIELDS = [
        'identifier' => 'primary_claim_id',
        'patient_dob' => 'patient_date_of_birth',
        'patient_id' => 'chart_number',
        'payer' => 'responsible_payer',
        'provider' => 'rendering_provider',
        'location' => 'location_name',
        'service_date' => 'date_of_service',
        'payments' => 'total_payment',
        'procedure' => 'procedure_code',
    ];

    /** @var array<string, string> */
    private const TRICITY_FIELDS = [
        'identifier' => 'bill_id',
        'patient_dob' => 'patient_dob',
        'patient_id' => 'patient_id',
        'payer' => 'payer_name',
        'provider' => 'primary_provider',
        'location' => 'location',
        'service_date' => 'service_date_start',
        'payments' => 'payments',
        'procedure' => 'procedure_code',
    ];

    public static function isPrinciple(string $account): bool
    {
        return $account === AccountType::Principle->value;
    }

    public static function field(string $account, string $semantic): string
    {
        $fields = self::isPrinciple($account) ? self::PRINCIPLE_FIELDS : self::TRICITY_FIELDS;

        return $fields[$semantic] ?? $semantic;
    }

    public static function expression(string $account, string $semantic): ?string
    {
        if (self::isPrinciple($account)) {
            $field = self::PRINCIPLE_FIELDS[$semantic] ?? null;

            return $field === null ? null : "NULLIF({$field}, '')";
        }

        return match ($semantic) {
            'identifier' => "COALESCE(NULLIF(bill_id, ''), NULLIF(external_id, ''))",
            'payer' => "COALESCE(NULLIF(payer_name, ''), NULLIF(payer, ''))",
            'provider' => "COALESCE(NULLIF(primary_provider, ''), NULLIF(provider, ''))",
            'location' => "COALESCE(NULLIF(location, ''), NULLIF(practice_location, ''))",
            'procedure' => "COALESCE(NULLIF(procedure_code, ''), NULLIF(cpt_code, ''))",
            default => isset(self::TRICITY_FIELDS[$semantic]) ? self::TRICITY_FIELDS[$semantic] : null,
        };
    }

    public static function supports(string $account, string $feature): bool
    {
        if (! self::isPrinciple($account)) {
            return true;
        }

        return ! in_array($feature, [
            'true_balance',
            'collection_percent',
            'modmed_status',
            'invoice_status',
            'cf_invoice_date',
            'place_of_service',
            'posted_date',
            'payer_cpt',
        ], true);
    }
}
