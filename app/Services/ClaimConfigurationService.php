<?php

namespace App\Services;

use App\Models\ClaimConfigurationOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClaimConfigurationService
{
    public const WORK_STATUS = 'work_status';

    public const CREDIT_STATUS = 'credit_status';

    public const CREDIT_REASON = 'credit_reason';

    public const DENIAL_REASON = 'denial_reason';

    /** @return array<string, string> */
    public function typeLabels(): array
    {
        return [
            self::WORK_STATUS => 'Work Status',
            self::CREDIT_STATUS => 'Credit Status',
            self::CREDIT_REASON => 'Credit Reason',
            self::DENIAL_REASON => 'Denial Reason',
        ];
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

    /** @return array<int, array{value: string, label: string, color: string|null}> */
    public function selectOptions(string $account, string $type): array
    {
        return $this->options($account, $type)
            ->map(fn (ClaimConfigurationOption $option): array => [
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

    public function resolveDenialReason(string $account, ?string $reason): ?string
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
            return $existing->value;
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

        return $option->value;
    }
}
