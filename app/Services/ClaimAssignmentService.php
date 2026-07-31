<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClaimAssignmentService
{
    private const BALANCE_VALUE_EXPRESSION = 'COALESCE(true_balance, balance)';

    private const BALANCE_SUM_EXPRESSION = 'COALESCE(true_balance, balance, 0)';

    /** @var array<string, string> */
    private const GROUP_LABELS = [
        'procedure_code' => 'CPT code',
        'payer_name' => 'Payer',
        'primary_provider' => 'Primary provider',
        'denial_reason' => 'Denial reason',
        'service_month' => 'Service month',
    ];

    /** @return array<int, array{key: string, label: string}> */
    public function groupDefinitions(): array
    {
        return collect(self::GROUP_LABELS)
            ->map(fn (string $label, string $key): array => compact('key', 'label'))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, User>  $assignees
     * @return array{summary: array<string, int|float|null>, workloads: array<int, array<string, int|float|string|null>>}
     */
    public function assignmentSnapshot(string $account, Collection $assignees): array
    {
        $unassigned = $this->unassignedRows($account);
        $unassignedBalanceRows = (clone $unassigned)
            ->whereRaw(self::BALANCE_VALUE_EXPRESSION.' IS NOT NULL')
            ->count();
        $assigneeIds = $assignees->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $assignedGroupRows = $this->assignedGroupRows($account);
        $assignedSummary = DB::query()
            ->fromSub(clone $assignedGroupRows, 'assigned_group_rows')
            ->selectRaw('COUNT(DISTINCT bill_id) as claim_groups')
            ->selectRaw('COUNT(*) as claim_lines')
            ->selectRaw('SUM(CASE WHEN '.self::BALANCE_VALUE_EXPRESSION.' IS NOT NULL THEN 1 ELSE 0 END) as balance_rows')
            ->selectRaw('SUM('.self::BALANCE_SUM_EXPRESSION.') as total_true_balance')
            ->first();
        $assignedRows = DB::query()
            ->fromSub($assignedGroupRows, 'assigned_group_rows')
            ->whereIn('group_assigned_to', $assigneeIds)
            ->selectRaw('group_assigned_to as assigned_to')
            ->selectRaw('COUNT(DISTINCT bill_id) as claim_groups')
            ->selectRaw('COUNT(*) as claim_lines')
            ->selectRaw('SUM(CASE WHEN '.self::BALANCE_VALUE_EXPRESSION.' IS NOT NULL THEN 1 ELSE 0 END) as balance_rows')
            ->selectRaw('SUM('.self::BALANCE_SUM_EXPRESSION.') as total_true_balance')
            ->groupBy('group_assigned_to')
            ->get()
            ->keyBy(fn ($row): int => (int) $row->assigned_to);

        $workloads = $assignees->map(function (User $assignee) use ($assignedRows): array {
            $stats = $assignedRows->get($assignee->id);
            $balanceRows = (int) ($stats?->balance_rows ?? 0);

            return [
                'id' => (int) $assignee->id,
                'name' => $assignee->name,
                'email' => $assignee->email,
                'claim_groups' => (int) ($stats?->claim_groups ?? 0),
                'claim_lines' => (int) ($stats?->claim_lines ?? 0),
                'balance_rows' => $balanceRows,
                'total_true_balance' => $balanceRows > 0 ? (float) $stats->total_true_balance : null,
            ];
        })->values();

        $assignedSummaryBalanceRows = (int) ($assignedSummary->balance_rows ?? 0);

        return [
            'summary' => [
                'claim_groups' => (clone $unassigned)->distinct()->count('bill_id'),
                'claim_lines' => (clone $unassigned)->count(),
                'balance_rows' => $unassignedBalanceRows,
                'total_true_balance' => $unassignedBalanceRows > 0
                    ? (float) (clone $unassigned)->sum(DB::raw(self::BALANCE_SUM_EXPRESSION))
                    : null,
                'assigned_claim_groups' => (int) ($assignedSummary->claim_groups ?? 0),
                'assigned_claim_lines' => (int) ($assignedSummary->claim_lines ?? 0),
                'assigned_balance_rows' => $assignedSummaryBalanceRows,
                'assigned_total_true_balance' => $assignedSummaryBalanceRows > 0
                    ? (float) $assignedSummary->total_true_balance
                    : null,
            ],
            'workloads' => $workloads->all(),
        ];
    }

    /**
     * @return array{
     *     data: array<int, array{id: string, value: string, name: string, count: int, claim_count: int, balance: ?float, total_balance: ?float}>,
     *     current_page: int,
     *     per_page: int,
     *     total: int,
     *     last_page: int,
     *     has_more: bool
     * }
     */
    public function options(string $account, string $groupBy, ?string $search = null, int $page = 1, int $perPage = 10): array
    {
        $expression = $this->groupExpression($groupBy);
        if ($expression === null) {
            return [
                'data' => [],
                'current_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
                'has_more' => false,
            ];
        }

        $query = $this->unassignedRows($account)
            ->when($groupBy === 'denial_reason', fn (Builder $query) => $query
                ->leftJoin('claim_configuration_options as grouped_configuration', function ($join): void {
                    $join->on('grouped_configuration.id', '=', 'claims.denial_reason_id')
                        ->where('grouped_configuration.option_type', ClaimConfigurationService::DENIAL_REASON);
                }))
            ->whereRaw("{$expression} IS NOT NULL")
            ->when($groupBy !== 'denial_reason', fn (Builder $query) => $query->whereRaw("{$expression} != ''"))
            ->when(trim((string) $search) !== '', function (Builder $query) use ($expression, $groupBy, $search): void {
                $searchExpression = $groupBy === 'denial_reason'
                    ? "COALESCE(grouped_configuration.label, grouped_configuration.value, '')"
                    : $expression;

                $query->whereRaw("{$searchExpression} LIKE ?", ['%'.trim((string) $search).'%']);
            })
            ->selectRaw("{$expression} as group_value")
            ->when(
                $groupBy === 'denial_reason',
                fn (Builder $query) => $query->selectRaw('MAX(grouped_configuration.label) as group_name'),
            )
            ->selectRaw('COUNT(DISTINCT bill_id) as claim_count')
            ->selectRaw('SUM(CASE WHEN '.self::BALANCE_VALUE_EXPRESSION.' IS NOT NULL THEN 1 ELSE 0 END) as balance_rows')
            ->selectRaw('SUM('.self::BALANCE_SUM_EXPRESSION.') as total_balance')
            ->groupByRaw($expression);

        $total = DB::query()->fromSub(clone $query, 'distribution_options')->count();
        $currentPage = max($page, 1);
        $perPage = min(max($perPage, 5), 200);
        $lastPage = max((int) ceil($total / $perPage), 1);
        $options = (clone $query)
            ->orderByDesc('claim_count')
            ->orderBy('group_value')
            ->forPage($currentPage, $perPage)
            ->get();

        return [
            'data' => $options->map(fn ($option): array => [
                'id' => (string) $option->group_value,
                'value' => (string) $option->group_value,
                'name' => (string) ($option->group_name ?? $option->group_value),
                'count' => (int) $option->claim_count,
                'claim_count' => (int) $option->claim_count,
                'balance' => (int) $option->balance_rows > 0 ? (float) $option->total_balance : null,
                'total_balance' => (int) $option->balance_rows > 0 ? (float) $option->total_balance : null,
            ])->all(),
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_more' => $currentPage < $lastPage,
        ];
    }

    /**
     * @param  array<int, string>  $groupValues
     * @param  Collection<int, User>  $assignees
     * @return array<string, mixed>
     */
    public function preview(string $account, string $groupBy, array $groupValues, Collection $assignees): array
    {
        $rows = $this->candidateRows($account, $groupBy, $groupValues)->get();

        return $this->buildDistributionPlan($rows, $assignees);
    }

    /**
     * @param  array<int, string>  $groupValues
     * @param  Collection<int, User>  $assignees
     * @return array<string, mixed>
     */
    public function distribute(string $account, string $groupBy, array $groupValues, Collection $assignees): array
    {
        return DB::transaction(function () use ($account, $groupBy, $groupValues, $assignees): array {
            $rows = $this->candidateRows($account, $groupBy, $groupValues)->lockForUpdate()->get();
            $plan = $this->buildDistributionPlan($rows, $assignees, true);

            foreach ($plan['distribution'] as $bucket) {
                foreach ($bucket['bill_ids'] as $billId) {
                    Claim::query()
                        ->where('account_type', $account)
                        ->where('bill_id', $billId)
                        ->whereNull('assigned_to')
                        ->update([
                            'assigned_to' => $bucket['id'],
                            'status' => DB::raw("CASE WHEN status = 'new' THEN 'in_progress' ELSE status END"),
                            'updated_at' => now(),
                        ]);
                }
            }

            return $plan;
        });
    }

    public function isValidGroup(string $groupBy): bool
    {
        return $groupBy === 'all' || array_key_exists($groupBy, self::GROUP_LABELS);
    }

    private function unassignedRows(string $account): Builder
    {
        return Claim::query()
            ->where('claims.account_type', $account)
            ->whereNull('claims.assigned_to')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('claims as assigned_sibling')
                    ->whereColumn('assigned_sibling.account_type', 'claims.account_type')
                    ->whereColumn('assigned_sibling.bill_id', 'claims.bill_id')
                    ->whereNotNull('assigned_sibling.assigned_to');
            });
    }

    private function assignedGroupRows(string $account): Builder
    {
        // A partially assigned Bill ID is one workload. Attribute every sibling
        // line to the most recently updated assigned line so nothing is omitted
        // or counted more than once.
        return Claim::query()
            ->where('claims.account_type', $account)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('claims as assigned_sibling')
                    ->whereColumn('assigned_sibling.account_type', 'claims.account_type')
                    ->whereColumn('assigned_sibling.bill_id', 'claims.bill_id')
                    ->whereNotNull('assigned_sibling.assigned_to');
            })
            ->select('claims.*')
            ->selectSub(function ($query): void {
                $query->from('claims as group_owner')
                    ->select('group_owner.assigned_to')
                    ->whereColumn('group_owner.account_type', 'claims.account_type')
                    ->whereColumn('group_owner.bill_id', 'claims.bill_id')
                    ->whereNotNull('group_owner.assigned_to')
                    ->orderByDesc('group_owner.updated_at')
                    ->orderByDesc('group_owner.id')
                    ->limit(1);
            }, 'group_assigned_to');
    }

    /** @param array<int, string> $groupValues */
    private function candidateRows(string $account, string $groupBy, array $groupValues): Builder
    {
        $query = $this->unassignedRows($account)
            ->select(['claims.id', 'claims.bill_id'])
            ->selectRaw(self::BALANCE_VALUE_EXPRESSION.' as true_balance');

        if ($groupBy === 'all') {
            return $query;
        }

        $expression = $this->groupExpression($groupBy);
        $values = array_values(array_unique(array_filter(array_map(
            fn (mixed $value): string => trim((string) $value),
            $groupValues,
        ))));

        if ($expression === null || $values === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn(DB::raw($expression), $values);
    }

    private function groupExpression(string $groupBy): ?string
    {
        return match ($groupBy) {
            'procedure_code' => "COALESCE(NULLIF(procedure_code, ''), NULLIF(cpt_code, ''))",
            'payer_name' => "COALESCE(NULLIF(payer_name, ''), NULLIF(payer, ''))",
            'primary_provider' => "COALESCE(NULLIF(primary_provider, ''), NULLIF(provider, ''))",
            'denial_reason' => 'claims.denial_reason_id',
            'service_month' => 'SUBSTR(COALESCE(service_date_start, date_of_service), 1, 7)',
            default => null,
        };
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, User>  $assignees
     * @return array<string, mixed>
     */
    private function buildDistributionPlan(Collection $rows, Collection $assignees, bool $includeBillIds = false): array
    {
        $groups = $rows
            ->groupBy('bill_id')
            ->map(function (Collection $claimRows, string $billId): array {
                return [
                    'bill_id' => $billId,
                    'line_count' => $claimRows->count(),
                    'balance_rows' => $claimRows->whereNotNull('true_balance')->count(),
                    'balance' => (float) $claimRows->sum(fn ($row): float => (float) ($row->true_balance ?? 0)),
                ];
            })
            ->sort(function (array $left, array $right): int {
                return ($right['balance'] <=> $left['balance'])
                    ?: ($right['line_count'] <=> $left['line_count'])
                    ?: strcmp($left['bill_id'], $right['bill_id']);
            })
            ->values();

        $distribution = $assignees->values()->map(fn (User $assignee): array => [
            'id' => (int) $assignee->id,
            'name' => $assignee->name,
            'email' => $assignee->email,
            'assign_count' => 0,
            'assign_line_count' => 0,
            'assign_balance' => 0.0,
            'bill_ids' => [],
        ])->all();

        foreach ($groups as $group) {
            if ($distribution === []) {
                break;
            }

            $targetIndex = 0;
            foreach ($distribution as $index => $bucket) {
                $betterBalance = $bucket['assign_balance'] < $distribution[$targetIndex]['assign_balance'];
                $equalBalance = abs($bucket['assign_balance'] - $distribution[$targetIndex]['assign_balance']) < 0.000001;
                $betterCount = $bucket['assign_count'] < $distribution[$targetIndex]['assign_count'];

                if ($betterBalance || ($equalBalance && $betterCount)) {
                    $targetIndex = $index;
                }
            }

            $distribution[$targetIndex]['assign_count']++;
            $distribution[$targetIndex]['assign_line_count'] += $group['line_count'];
            $distribution[$targetIndex]['assign_balance'] += $group['balance'];
            $distribution[$targetIndex]['bill_ids'][] = $group['bill_id'];
        }

        $balanceRows = (int) $groups->sum('balance_rows');
        $totalBalance = (float) $groups->sum('balance');
        $balanceAvailable = $balanceRows > 0;
        $assigneeCount = count($distribution);

        $formattedDistribution = array_map(function (array $bucket) use ($balanceAvailable, $includeBillIds): array {
            if (! $balanceAvailable) {
                $bucket['assign_balance'] = null;
            }
            if (! $includeBillIds) {
                unset($bucket['bill_ids']);
            }

            return $bucket;
        }, $distribution);

        return [
            'total_claims' => $groups->count(),
            'total_lines' => (int) $groups->sum('line_count'),
            'balance_rows' => $balanceRows,
            'total_balance' => $balanceAvailable ? $totalBalance : null,
            'assignee_count' => $assigneeCount,
            'target_balance' => $balanceAvailable && $assigneeCount > 0 ? $totalBalance / $assigneeCount : null,
            'distribution' => $formattedDistribution,
        ];
    }
}
