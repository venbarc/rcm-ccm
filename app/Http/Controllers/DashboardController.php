<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Services\ClaimConfigurationService;
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

    private const TRUE_CHARGE_EXPRESSION = 'COALESCE(true_charge, billed_amount, 0)';

    private const PAYMENTS_EXPRESSION = 'COALESCE(payments, 0)';

    private const CF_INVOICE_AMOUNT_EXPRESSION = 'COALESCE(cf_invoice_amount, 0)';

    private const BILL_ID_EXPRESSION = "COALESCE(NULLIF(TRIM(bill_id), ''), NULLIF(TRIM(external_id), ''))";

    private const CPT_EXPRESSION = "COALESCE(NULLIF(TRIM(procedure_code), ''), NULLIF(TRIM(cpt_code), ''))";

    private const MODMED_STATUS_EXPRESSION = "NULLIF(TRIM(modmed_claim_status), '')";

    private const WORK_STATUS_EXPRESSION = "COALESCE(NULLIF(TRIM(work_status), ''), 'draft')";

    public function __construct(private readonly ClaimConfigurationService $configurations) {}

    public function __invoke(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $filters = $this->resolveDateRange($request);
        $claims = Claim::query()->where('account_type', $account->value);
        $this->applyDateRange($claims, $filters['startDate'], $filters['endDate']);
        $isAdmin = (bool) $request->user()?->is_admin;
        $workStatuses = $this->configurations->selectOptions($account->value, ClaimConfigurationService::WORK_STATUS);
        $modMedStatusLabels = $this->configurations->labelMap($account->value, ClaimConfigurationService::MODMED_CLAIM_STATUS);
        $modMedStatusColors = $this->configurations->colorMap($account->value, ClaimConfigurationService::MODMED_CLAIM_STATUS);

        $totalQuery = (clone $claims)
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount');

        if ($isAdmin) {
            $totalQuery
                ->selectRaw('COUNT(DISTINCT '.self::BILL_ID_EXPRESSION.') as bill_count')
                ->selectRaw('SUM(COALESCE(units, 0)) as units')
                ->selectRaw('SUM('.self::TRUE_CHARGE_EXPRESSION.') as true_charge')
                ->selectRaw('SUM('.self::PAYMENTS_EXPRESSION.') as payments')
                ->selectRaw('SUM('.self::CF_INVOICE_AMOUNT_EXPRESSION.') as cf_invoice_amount');
        }

        $total = $totalQuery->first();
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
        $adminSummaryProps = [];

        if ($isAdmin) {
            $summaryTotal = $this->financialSummaryRow($total);
            $adminSummaryProps = [
                'cptSummary' => [
                    'rows' => $this->groupedFinancialSummary($claims, self::CPT_EXPRESSION),
                    'total' => $summaryTotal,
                ],
                'modmedStatusSummary' => [
                    'rows' => $this->groupedFinancialSummary(
                        $claims,
                        self::MODMED_STATUS_EXPRESSION,
                        $modMedStatusLabels,
                        $modMedStatusColors,
                    ),
                    'total' => $summaryTotal,
                ],
            ];
        }

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
            'claimsByStatus' => $this->claimsByStatus($claims, $workStatuses),
            ...$adminSummaryProps,
        ]);
    }

    /** @return array<int, array{status: string, label: string, count: int, amount: float}> */
    private function claimsByStatus(Builder $query, array $workStatuses): array
    {
        $statuses = (clone $query)
            ->selectRaw(self::WORK_STATUS_EXPRESSION.' as status_key')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->groupByRaw(self::WORK_STATUS_EXPRESSION)
            ->get()
            ->keyBy('status_key');

        return collect($workStatuses)
            ->map(function (array $status) use ($statuses): array {
                $row = $statuses->get($status['value']);

                return [
                    'status' => $status['value'],
                    'label' => $status['label'],
                    'color' => $status['color'],
                    'count' => (int) ($row?->line_count ?? 0),
                    'amount' => (float) ($row?->amount ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{
     *     group: string|null,
     *     groupLabel: string|null,
     *     groupColor: string|null,
     *     billCount: int,
     *     cptCount: int,
     *     units: float,
     *     trueCharge: float,
     *     payments: float,
     *     trueBalance: float,
     *     collectionPercent: float,
     *     cfInvoiceAmount: float
     * }>
     */
    private function groupedFinancialSummary(
        Builder $query,
        string $groupExpression,
        array $groupLabels = [],
        array $groupColors = [],
    ): array {
        return (clone $query)
            ->selectRaw("{$groupExpression} as group_key")
            ->selectRaw('COUNT(DISTINCT '.self::BILL_ID_EXPRESSION.') as bill_count')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM(COALESCE(units, 0)) as units')
            ->selectRaw('SUM('.self::TRUE_CHARGE_EXPRESSION.') as true_charge')
            ->selectRaw('SUM('.self::PAYMENTS_EXPRESSION.') as payments')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as true_balance')
            ->selectRaw('SUM('.self::CF_INVOICE_AMOUNT_EXPRESSION.') as cf_invoice_amount')
            ->groupByRaw($groupExpression)
            ->orderByDesc('bill_count')
            ->orderByDesc('true_charge')
            ->get()
            ->map(function ($row) use ($groupColors, $groupLabels): array {
                $group = filled($row->group_key) ? (string) $row->group_key : null;

                return $this->financialSummaryRow(
                    $row,
                    $group,
                    $group === null ? null : ($groupLabels[$group] ?? $group),
                    $group === null ? null : ($groupColors[$group] ?? null),
                );
            })
            ->all();
    }

    /**
     * @return array{
     *     group: string|null,
     *     groupLabel: string|null,
     *     groupColor: string|null,
     *     billCount: int,
     *     cptCount: int,
     *     units: float,
     *     trueCharge: float,
     *     payments: float,
     *     trueBalance: float,
     *     collectionPercent: float,
     *     cfInvoiceAmount: float
     * }
     */
    private function financialSummaryRow(
        object $row,
        ?string $group = null,
        ?string $groupLabel = null,
        ?string $groupColor = null,
    ): array {
        $trueCharge = (float) ($row->true_charge ?? 0);
        $payments = (float) ($row->payments ?? 0);

        return [
            'group' => $group,
            'groupLabel' => $groupLabel ?? $group,
            'groupColor' => $groupColor,
            'billCount' => (int) ($row->bill_count ?? 0),
            'cptCount' => (int) ($row->line_count ?? 0),
            'units' => (float) ($row->units ?? 0),
            'trueCharge' => $trueCharge,
            'payments' => $payments,
            'trueBalance' => (float) ($row->true_balance ?? $row->amount ?? 0),
            'collectionPercent' => $trueCharge > 0 ? round(($payments / $trueCharge) * 100, 2) : 0.0,
            'cfInvoiceAmount' => (float) ($row->cf_invoice_amount ?? 0),
        ];
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
