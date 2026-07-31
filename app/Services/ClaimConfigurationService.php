<?php

namespace App\Services;

use App\Models\ClaimConfigurationOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClaimConfigurationService
{
    private const AUTOMATIC_COLORS = [
        '#F8FAFC',
        '#F3E8FF',
        '#DBEAFE',
        '#FEE2E2',
        '#F3F4F6',
        '#FEF3C7',
        '#E0F2FE',
        '#CCFBF1',
        '#DCFCE7',
        '#FFEDD5',
        '#FCE7F3',
        '#E0E7FF',
        '#ECFCCB',
        '#FFE4E6',
        '#CFFAFE',
        '#D1FAE5',
    ];

    /** @var array<string, ClaimConfigurationOption> */
    private array $resolvedModMedStatuses = [];

    public const WORK_STATUS = 'work_status';

    public const MODMED_CLAIM_STATUS = 'modmed_claim_status';

    public const CREDIT_STATUS = 'credit_status';

    public const CREDIT_REASON = 'credit_reason';

    public const DENIAL_REASON = 'denial_reason';

    /** @return array<string, string> */
    public function typeLabels(): array
    {
        return [
            self::WORK_STATUS => 'Work Status',
            self::MODMED_CLAIM_STATUS => 'ModMed Claim Status',
            self::CREDIT_STATUS => 'Credit Status',
            self::CREDIT_REASON => 'Credit Reason',
            self::DENIAL_REASON => 'Denial Reason',
        ];
    }

    public function usesColor(string $type): bool
    {
        return in_array($type, [self::WORK_STATUS, self::MODMED_CLAIM_STATUS], true);
    }

    public function claimReferenceColumn(string $type): ?string
    {
        return match ($type) {
            self::WORK_STATUS => 'work_status_id',
            self::MODMED_CLAIM_STATUS => 'modmed_claim_status_id',
            self::CREDIT_STATUS => 'credit_status_id',
            self::CREDIT_REASON => 'credit_reason_id',
            self::DENIAL_REASON => 'denial_reason_id',
            default => null,
        };
    }

    /** @return Builder<ClaimConfigurationOption> */
    public function query(string $account, string $type): Builder
    {
        return ClaimConfigurationOption::query()
            ->where('account_type', $account)
            ->where('option_type', $type);
    }

    /** @return Collection<int, ClaimConfigurationOption> */
    public function options(string $account, string $type): Collection
    {
        return $this->query($account, $type)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /** @return array<int, array{id: int, value: string, label: string, color: string|null}> */
    public function selectOptions(string $account, string $type): array
    {
        return $this->options($account, $type)
            ->map(fn (ClaimConfigurationOption $option): array => [
                'id' => $option->id,
                'value' => $option->value,
                'label' => $option->label,
                'color' => $option->color,
            ])
            ->all();
    }

    /** @return array<int, string> */
    public function values(string $account, string $type): array
    {
        return $this->query($account, $type)
            ->orderBy('sort_order')
            ->pluck('value')
            ->all();
    }

    /** @return array<string, string> */
    public function labelMap(string $account, string $type): array
    {
        return $this->query($account, $type)
            ->pluck('label', 'value')
            ->all();
    }

    /** @return array<string, string> */
    public function colorMap(string $account, string $type): array
    {
        return $this->query($account, $type)
            ->whereNotNull('color')
            ->pluck('color', 'value')
            ->all();
    }

    /** @return array<int, string> */
    public function labelMapById(string $account, string $type): array
    {
        return $this->query($account, $type)
            ->pluck('label', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function colorMapById(string $account, string $type): array
    {
        return $this->query($account, $type)
            ->whereNotNull('color')
            ->pluck('color', 'id')
            ->all();
    }

    public function optionForValue(string $account, string $type, mixed $value): ?ClaimConfigurationOption
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        return $this->query($account, $type)
            ->where(function (Builder $query) use ($value): void {
                if (is_numeric($value)) {
                    $query->whereKey((int) $value)->orWhere('value', $value);
                } else {
                    $query->where('value', $value);
                }

                $query->orWhereRaw('LOWER(label) = ?', [mb_strtolower($value)]);
            })
            ->first();
    }

    public function idForValue(string $account, string $type, mixed $value): ?int
    {
        return $this->optionForValue($account, $type, $value)?->id;
    }

    public function resolveDenialReason(string $account, ?string $reason): ?string
    {
        return $this->resolveDenialReasonOption($account, $reason)?->value;
    }

    public function resolveDenialReasonOption(string $account, ?string $reason): ?ClaimConfigurationOption
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            return null;
        }

        $existing = $this->query($account, self::DENIAL_REASON)
            ->where(function (Builder $query) use ($reason): void {
                $query->where('value', $reason)
                    ->orWhereRaw('LOWER(label) = ?', [mb_strtolower($reason)]);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $option = ClaimConfigurationOption::query()->firstOrCreate(
            [
                'account_type' => $account,
                'option_type' => self::DENIAL_REASON,
                'value' => $reason,
            ],
            [
                'label' => $reason,
                'sort_order' => ((int) $this->query($account, self::DENIAL_REASON)->max('sort_order')) + 1,
                'added_by' => null,
            ],
        );

        return $option;
    }

    public function resolveModMedClaimStatus(string $account, ?string $status): ?string
    {
        return $this->resolveModMedClaimStatusOption($account, $status)?->value;
    }

    public function resolveModMedClaimStatusOption(string $account, ?string $status): ?ClaimConfigurationOption
    {
        $status = trim((string) $status);
        if ($status === '') {
            return null;
        }
        $cacheKey = $account.':'.mb_strtolower($status);
        if (isset($this->resolvedModMedStatuses[$cacheKey])) {
            return $this->resolvedModMedStatuses[$cacheKey];
        }

        $existing = $this->query($account, self::MODMED_CLAIM_STATUS)
            ->where(function (Builder $query) use ($status): void {
                $query->where('value', $status)
                    ->orWhereRaw('LOWER(label) = ?', [mb_strtolower($status)]);
            })
            ->first();

        if ($existing) {
            return $this->resolvedModMedStatuses[$cacheKey] = $existing;
        }

        $option = ClaimConfigurationOption::query()->firstOrCreate(
            [
                'account_type' => $account,
                'option_type' => self::MODMED_CLAIM_STATUS,
                'value' => $status,
            ],
            [
                'label' => $status,
                'color' => $this->nextAutomaticColor($account, self::MODMED_CLAIM_STATUS, $status),
                'sort_order' => ((int) $this->query($account, self::MODMED_CLAIM_STATUS)->max('sort_order')) + 1,
                'added_by' => null,
            ],
        );

        return $this->resolvedModMedStatuses[$cacheKey] = $option;
    }

    private function nextAutomaticColor(string $account, string $type, string $seed): string
    {
        $usedColors = $this->colorMap($account, $type);
        $usedColors = array_map('strtoupper', array_values($usedColors));

        foreach (self::AUTOMATIC_COLORS as $color) {
            if (! in_array($color, $usedColors, true)) {
                return $color;
            }
        }

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $hash = hexdec(substr(hash('sha256', "{$seed}:{$attempt}"), 0, 6));
            $red = 220 + (($hash >> 16) & 31);
            $green = 220 + (($hash >> 8) & 31);
            $blue = 220 + ($hash & 31);
            $color = sprintf('#%02X%02X%02X', $red, $green, $blue);

            if (! in_array($color, $usedColors, true)) {
                return $color;
            }
        }

        throw new \RuntimeException("Unable to allocate a unique {$type} color for {$account}.");
    }
}
