<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Support\CurrentAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const DATE_PRESETS = ['week', 'month', 'year', 'all', 'custom'];

    private const BALANCE_EXPRESSION = 'COALESCE(true_balance, balance, 0)';

    private const WORK_STATUS_EXPRESSION = "COALESCE(NULLIF(TRIM(work_status), ''), 'draft')";

    private const WORK_STATUSES = [
        'draft', 'paid', 'historical_posted_payments', 'rebilled', 'appeal',
        'pending', 'void', 'corrected', 'patient_balance',
    ];

    public function __invoke(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $filters = $this->resolveDateRange($request);
        $claims = Claim::query()->where('account_type', $account->value);
        $this->applyDateRange($claims, $filters['startDate'], $filters['endDate']);

        $total = (clone $claims)
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->first();
        $worked = (clone $claims)
            ->whereRaw(self::WORK_STATUS_EXPRESSION.' != ?', ['draft'])
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->first();
        $paid = (clone $claims)
            ->whereRaw(self::WORK_STATUS_EXPRESSION.' = ?', ['paid'])
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->first();

        $totalCount = (int) ($total->line_count ?? 0);
        $totalAmount = (float) ($total->amount ?? 0);
        $workedCount = (int) ($worked->line_count ?? 0);
        $workedAmount = (float) ($worked->amount ?? 0);
        $paidCount = (int) ($paid->line_count ?? 0);
        $paidAmount = (float) ($paid->amount ?? 0);

        return Inertia::render('dashboard', [
            'accountLabel' => $account->label(),
            'filters' => [
                'preset' => $filters['preset'],
                'start' => $filters['startDate']?->toDateString(),
                'end' => $filters['endDate']?->toDateString(),
                'label' => $filters['label'],
                'presetLabel' => $filters['presetLabel'],
            ],
            'workSummary' => [
                'totalCount' => $totalCount,
                'totalAmount' => $totalAmount,
                'workedCount' => $workedCount,
                'workedAmount' => $workedAmount,
                'remainingCount' => max($totalCount - $paidCount, 0),
                'remainingAmount' => max($totalAmount - $paidAmount, 0),
                'paidCount' => $paidCount,
                'paidAmount' => $paidAmount,
                'workedProgress' => $totalCount > 0
                    ? round(($paidCount / $totalCount) * 100, 2)
                    : 0.0,
            ],
            'claimsByStatus' => $this->claimsByStatus($claims),
        ]);
    }

    /** @return array<int, array{status: string, label: string, count: int, amount: float}> */
    private function claimsByStatus(Builder $query): array
    {
        $statuses = (clone $query)
            ->selectRaw(self::WORK_STATUS_EXPRESSION.' as status_key')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->groupByRaw(self::WORK_STATUS_EXPRESSION)
            ->get()
            ->keyBy('status_key');

        return collect(self::WORK_STATUSES)
            ->map(function (string $status) use ($statuses): array {
                $row = $statuses->get($status);

                return [
                    'status' => $status,
                    'label' => str($status)->replace('_', ' ')->title()->toString(),
                    'count' => (int) ($row?->line_count ?? 0),
                    'amount' => (float) ($row?->amount ?? 0),
                ];
            })
            ->all();
    }

    /** @return array{preset: string, startDate: Carbon|null, endDate: Carbon|null, label: string, presetLabel: string} */
    private function resolveDateRange(Request $request): array
    {
        $preset = (string) $request->input('preset', 'all');
        if (! in_array($preset, self::DATE_PRESETS, true)) {
            $preset = 'all';
        }

        $today = Carbon::today();
        $startDate = null;
        $endDate = null;
        $presetLabels = [
            'week' => 'This week',
            'month' => 'This month',
            'year' => 'This year',
            'all' => 'All time',
            'custom' => 'Custom range',
        ];

        if ($preset === 'custom') {
            $startDate = $this->parseDate($request->input('start'));
            $endDate = $this->parseDate($request->input('end'));
            if (! $startDate || ! $endDate) {
                $preset = 'month';
            }
        }

        if ($preset === 'week') {
            $startDate = $today->copy()->startOfWeek();
            $endDate = $today->copy()->endOfWeek();
        } elseif ($preset === 'month') {
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfMonth();
        } elseif ($preset === 'year') {
            $startDate = $today->copy()->startOfYear();
            $endDate = $today->copy()->endOfYear();
        } elseif ($preset === 'all') {
            $startDate = null;
            $endDate = null;
        }

        if ($startDate && $endDate && $startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'preset' => $preset,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'label' => $startDate && $endDate
                ? $startDate->format('M j, Y').' - '.$endDate->format('M j, Y')
                : 'All time',
            'presetLabel' => $presetLabels[$preset],
        ];
    }

    private function applyDateRange(Builder $query, ?Carbon $startDate, ?Carbon $endDate): void
    {
        $serviceDateExpression = 'COALESCE(service_date_start, date_of_service)';

        $query
            ->when($startDate, fn (Builder $inner) => $inner->whereDate(DB::raw($serviceDateExpression), '>=', $startDate->toDateString()))
            ->when($endDate, fn (Builder $inner) => $inner->whereDate(DB::raw($serviceDateExpression), '<=', $endDate->toDateString()));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
