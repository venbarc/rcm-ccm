<?php

namespace App\Enums;

enum SystemClaimConfiguration: string
{
    case WorkDraft = 'work_status:draft';
    case WorkPaid = 'work_status:paid';
    case WorkRebilled = 'work_status:rebilled';
    case WorkAppeal = 'work_status:appeal';
    case WorkPending = 'work_status:pending';
    case WorkVoid = 'work_status:void';
    case WorkCorrected = 'work_status:corrected';
    case WorkPatientBalance = 'work_status:patient_balance';
    case CreditStatusYes = 'credit_status:yes';
    case CreditStatusNo = 'credit_status:no';
    case CreditReasonInactiveInsurance = 'credit_reason:inactive_insurance';
    case CreditReasonNotCovered = 'credit_reason:not_covered_by_insurance';

    public function optionType(): string
    {
        return explode(':', $this->value, 2)[0];
    }

    public function internalValue(): string
    {
        return explode(':', $this->value, 2)[1];
    }

    public function label(): string
    {
        return match ($this) {
            self::WorkDraft => 'Draft',
            self::WorkPaid => 'Paid',
            self::WorkRebilled => 'Rebilled',
            self::WorkAppeal => 'Appeal',
            self::WorkPending => 'Pending',
            self::WorkVoid => 'Void',
            self::WorkCorrected => 'Corrected',
            self::WorkPatientBalance => 'Patient Balance',
            self::CreditStatusYes => 'Yes',
            self::CreditStatusNo => 'No',
            self::CreditReasonInactiveInsurance => 'Inactive Insurance',
            self::CreditReasonNotCovered => 'Not Covered by the Insurance',
        };
    }

    public function color(): ?string
    {
        return match ($this) {
            self::WorkDraft => '#F3F4F6',
            self::WorkPaid => '#DCFCE7',
            self::WorkRebilled => '#DBEAFE',
            self::WorkAppeal => '#F3E8FF',
            self::WorkPending => '#FEF3C7',
            self::WorkVoid => '#E2E8F0',
            self::WorkCorrected => '#CFFAFE',
            self::WorkPatientBalance => '#FCE7F3',
            default => null,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::WorkDraft, self::CreditStatusYes, self::CreditReasonInactiveInsurance => 0,
            self::WorkPaid, self::CreditStatusNo, self::CreditReasonNotCovered => 1,
            self::WorkRebilled => 2,
            self::WorkAppeal => 3,
            self::WorkPending => 4,
            self::WorkVoid => 5,
            self::WorkCorrected => 6,
            self::WorkPatientBalance => 7,
        };
    }

    /** @return array<int, self> */
    public static function forType(string $type): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $configuration): bool => $configuration->optionType() === $type,
        ));
    }

    public static function find(string $type, string $value): ?self
    {
        foreach (self::forType($type) as $configuration) {
            if ($configuration->internalValue() === $value) {
                return $configuration;
            }
        }

        return null;
    }
}
