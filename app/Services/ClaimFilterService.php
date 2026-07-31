<?php

namespace App\Services;

use App\Models\Claim;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ClaimFilterService
{
    public function __construct(private readonly ClaimConfigurationService $configurations) {}

    public const FILTER_KEYS = [
        'search',
        'modmed_claim_status',
        'invoiced_status',
        'payer_name',
        'primary_provider',
        'denial_reason',
        'work_status',
        'assigned_to',
        'worked_from',
        'worked_to',
        'service_month',
        'cf_invoice_from',
        'cf_invoice_to',
        'procedure_code',
    ];

    /** @param array<string, mixed> $filters */
    public function matchingLines(string $account, array $filters, ?int $userId = null): Builder
    {
        $query = Claim::query()->where('account_type', $account);
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('bill_id', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_id', 'like', "%{$search}%")
                    ->orWhereRaw($this->expression('payer_name').' LIKE ?', ["%{$search}%"])
                    ->orWhereRaw($this->expression('primary_provider').' LIKE ?', ["%{$search}%"])
                    ->orWhereRaw($this->expression('procedure_code').' LIKE ?', ["%{$search}%"]);
            });
        }

        $this->applyConfigurationFilter(
            $query,
            $account,
            ClaimConfigurationService::MODMED_CLAIM_STATUS,
            'modmed_claim_status',
            $filters['modmed_claim_status'] ?? null,
        );
        $this->applyExactFilter($query, 'invoiced_status', $filters['invoiced_status'] ?? null);
        $this->applyExpressionValuesFilter($query, $this->expression('payer_name'), $filters['payer_name'] ?? null);
        $this->applyExpressionExactFilter($query, $this->expression('primary_provider'), $filters['primary_provider'] ?? null);
        $this->applyConfigurationFilter(
            $query,
            $account,
            ClaimConfigurationService::DENIAL_REASON,
            'denial_reason',
            $filters['denial_reason'] ?? null,
        );
        $this->applyConfigurationFilter(
            $query,
            $account,
            ClaimConfigurationService::WORK_STATUS,
            'work_status',
            $filters['work_status'] ?? null,
        );
        $this->applyExpressionExactFilter($query, $this->expression('procedure_code'), $filters['procedure_code'] ?? null);

        $assignedTo = $filters['assigned_to'] ?? null;
        if ($assignedTo === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($assignedTo === 'me' && $userId !== null) {
            $query->where('assigned_to', $userId);
        } elseif (is_numeric($assignedTo)) {
            $query->where('assigned_to', (int) $assignedTo);
        }

        $this->applyDateFilter($query, 'updated_at', '>=', $filters['worked_from'] ?? null);
        $this->applyDateFilter($query, 'updated_at', '<=', $filters['worked_to'] ?? null);
        $this->applyDateFilter($query, 'cf_invoice_date', '>=', $filters['cf_invoice_from'] ?? null);
        $this->applyDateFilter($query, 'cf_invoice_date', '<=', $filters['cf_invoice_to'] ?? null);

        $serviceMonth = trim((string) ($filters['service_month'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}$/', $serviceMonth) === 1) {
            $month = Carbon::createFromFormat('!Y-m', $serviceMonth);
            $query->whereBetween('service_date_start', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);
        }

        return $query;
    }

    private function applyExactFilter(Builder $query, string $column, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));
        if ($value !== '' && $value !== 'all') {
            $query->where($column, $value);
        }
    }

    private function applyConfigurationFilter(
        Builder $query,
        string $account,
        string $type,
        string $legacyColumn,
        mixed $value,
    ): void {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || $value === 'all') {
            return;
        }

        $option = $this->configurations->optionForValue($account, $type, $value);
        if (! $option) {
            $query->whereRaw('1 = 0');

            return;
        }

        $referenceColumn = $this->configurations->claimReferenceColumn($type);
        $query->where(function (Builder $nested) use ($legacyColumn, $option, $referenceColumn): void {
            $nested->where($referenceColumn, $option->id)
                ->orWhere(function (Builder $fallback) use ($legacyColumn, $option, $referenceColumn): void {
                    $fallback->whereNull($referenceColumn)
                        ->where($legacyColumn, $option->value);
                });
        });
    }

    private function applyExpressionExactFilter(Builder $query, ?string $expression, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));
        if ($expression === null || $value === '' || $value === 'all') {
            return;
        }

        $query->whereRaw("{$expression} = ?", [$value]);
    }

    private function applyExpressionValuesFilter(Builder $query, ?string $expression, mixed $value): void
    {
        $values = $this->multiSelectValues($value);
        if ($expression === null || $values === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $query->whereRaw("{$expression} IN ({$placeholders})", $values);
    }

    /** @return array<int, string> */
    private function multiSelectValues(mixed $value): array
    {
        if (is_array($value)) {
            $values = $value;
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            $values = is_array($decoded) ? $decoded : [$value];
        } else {
            return [];
        }

        return collect($values)
            ->filter(fn ($item): bool => is_string($item))
            ->map(fn (string $item): string => trim($item))
            ->reject(fn (string $item): bool => $item === '' || $item === 'all')
            ->unique()
            ->values()
            ->all();
    }

    private function applyDateFilter(Builder $query, string $column, string $operator, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return;
        }

        try {
            $query->whereDate($column, $operator, Carbon::parse($value)->toDateString());
        } catch (\Throwable) {
            // Ignore malformed query-string dates and keep filtering available.
        }
    }

    private function expression(string $filter): ?string
    {
        return match ($filter) {
            'payer_name' => "COALESCE(NULLIF(payer_name, ''), NULLIF(payer, ''))",
            'primary_provider' => "COALESCE(NULLIF(primary_provider, ''), NULLIF(provider, ''))",
            'procedure_code' => "COALESCE(NULLIF(procedure_code, ''), NULLIF(cpt_code, ''))",
            default => null,
        };
    }
}
