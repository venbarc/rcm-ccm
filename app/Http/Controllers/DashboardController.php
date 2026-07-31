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

    public function __construct(private readonly ClaimConfigurationService $configurations) {}

    public function __invoke(Request $request): Response
    {
        $account = CurrentAccount::resolve($request);
        $filters = $this->resolveDateRange($request);
        $baseClaims = Claim::query()->where('account_type', $account->value);
        $workSummaryClaims = clone $baseClaims;
        $this->applyServiceDateRange($workSummaryClaims, $filters['startDate'], $filters['endDate']);

        $panelFilters = [
            'claimsByStatus' => $this->resolvePanelDateFilters($request, 'claims_status'),
            'cptSummary' => $this->resolvePanelDateFilters($request, 'cpt'),
            'modmedStatusSummary' => $this->resolvePanelDateFilters($request, 'modmed'),
            'invoicedSummary' => $this->resolvePanelDateFilters($request, 'invoiced', false),
            'creditStatusSummary' => $this->resolvePanelDateFilters($request, 'credit_status', false),
        ];

        $claimsByStatusQuery = clone $baseClaims;
        $this->applyPanelDateFilters($claimsByStatusQuery, $panelFilters['claimsByStatus']);
        $isAdmin = (bool) $request->user()?->is_admin;
        $workStatuses = $this->configurations->selectOptions($account->value, ClaimConfigurationService::WORK_STATUS);
        $draftStatusId = $this->configurations->idForValue($account->value, ClaimConfigurationService::WORK_STATUS, 'draft');
        $paidStatusId = $this->configurations->idForValue($account->value, ClaimConfigurationService::WORK_STATUS, 'paid');
        $modMedStatusLabels = $this->configurations->labelMapById($account->value, ClaimConfigurationService::MODMED_CLAIM_STATUS);
        $modMedStatusColors = $this->configurations->colorMapById($account->value, ClaimConfigurationService::MODMED_CLAIM_STATUS);

        $totalQuery = (clone $workSummaryClaims)
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount');

        $total = $totalQuery->first();
        $worked = (clone $workSummaryClaims)
            ->whereNotNull('work_status_id')
            ->when($draftStatusId, fn (Builder $query) => $query->where('work_status_id', '!=', $draftStatusId))
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->first();
        $paid = (clone $workSummaryClaims)
            ->when(
                $paidStatusId,
                fn (Builder $query) => $query->where('work_status_id', $paidStatusId),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            )
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
            $creditStatusLabels = $this->configurations->labelMap($account->value, ClaimConfigurationService::CREDIT_STATUS);
            $cptSummaryClaims = clone $baseClaims;
            $this->applyPanelDateFilters($cptSummaryClaims, $panelFilters['cptSummary']);

            $modmedStatusSummaryClaims = clone $baseClaims;
            $this->applyPanelDateFilters($modmedStatusSummaryClaims, $panelFilters['modmedStatusSummary']);

            $invoicedSummaryClaims = (clone $baseClaims)->whereNotNull('cf_invoice_date');
            $this->applyPanelDateFilters($invoicedSummaryClaims, $panelFilters['invoicedSummary']);

            $adminSummaryProps = [
                'cptSummary' => $this->financialSummary($cptSummaryClaims, self::CPT_EXPRESSION),
                'modmedStatusSummary' => $this->financialSummary(
                    $modmedStatusSummaryClaims,
                    'modmed_claim_status_id',
                    $modMedStatusLabels,
                    $modMedStatusColors,
                ),
                'invoicedSummary' => $this->invoicedSummary($invoicedSummaryClaims),
                'creditStatusSummary' => $this->creditStatusSummary(
                    $baseClaims,
                    $panelFilters['creditStatusSummary'],
                    $creditStatusLabels,
                ),
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
            'panelFilters' => collect($panelFilters)
                ->map(fn (array $range): array => [
                    'invoiceStart' => $range['invoiceStart']?->toDateString(),
                    'invoiceEnd' => $range['invoiceEnd']?->toDateString(),
                    'serviceStart' => $range['serviceStart']?->toDateString(),
                    'serviceEnd' => $range['serviceEnd']?->toDateString(),
                ])
                ->all(),
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
            'claimsByStatus' => $this->claimsByStatus($claimsByStatusQuery, $workStatuses),
            ...$adminSummaryProps,
        ]);
    }

    /**
     * @return array{
     *     rows: array<int, array{
     *         group: string|null,
     *         groupLabel: string|null,
     *         groupColor: string|null,
     *         billCount: int,
     *         cptCount: int,
     *         units: float,
     *         trueCharge: float,
     *         payments: float,
     *         trueBalance: float,
     *         collectionPercent: float,
     *         cfInvoiceAmount: float
     *     }>,
     *     total: array{
     *         group: string|null,
     *         groupLabel: string|null,
     *         groupColor: string|null,
     *         billCount: int,
     *         cptCount: int,
     *         units: float,
     *         trueCharge: float,
     *         payments: float,
     *         trueBalance: float,
     *         collectionPercent: float,
     *         cfInvoiceAmount: float
     *     }
     * }
     */
    private function financialSummary(
        Builder $query,
        string $groupExpression,
        array $groupLabels = [],
        array $groupColors = [],
    ): array {
        $total = (clone $query)
            ->selectRaw('COUNT(DISTINCT '.self::BILL_ID_EXPRESSION.') as bill_count')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM(COALESCE(units, 0)) as units')
            ->selectRaw('SUM('.self::TRUE_CHARGE_EXPRESSION.') as true_charge')
            ->selectRaw('SUM('.self::PAYMENTS_EXPRESSION.') as payments')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as true_balance')
            ->selectRaw('SUM('.self::CF_INVOICE_AMOUNT_EXPRESSION.') as cf_invoice_amount')
            ->first();

        return [
            'rows' => $this->groupedFinancialSummary(
                $query,
                $groupExpression,
                $groupLabels,
                $groupColors,
            ),
            'total' => $this->financialSummaryRow($total),
        ];
    }

    /** @return array{rows: array<int, array{cpt: string|null, units: float}>, totalUnits: float} */
    private function invoicedSummary(Builder $query): array
    {
        $rows = (clone $query)
            ->selectRaw(self::CPT_EXPRESSION.' as cpt')
            ->selectRaw('SUM(COALESCE(units, 0)) as units')
            ->groupByRaw(self::CPT_EXPRESSION)
            ->orderByDesc('units')
            ->orderBy('cpt')
            ->get()
            ->map(fn ($row): array => [
                'cpt' => filled($row->cpt) ? (string) $row->cpt : null,
                'units' => (float) ($row->units ?? 0),
            ])
            ->all();

        return [
            'rows' => $rows,
            'totalUnits' => (float) collect($rows)->sum('units'),
        ];
    }

    /**
     * @param  array{invoiceStart: Carbon|null, invoiceEnd: Carbon|null, serviceStart: Carbon|null, serviceEnd: Carbon|null}  $filters
     * @param  array<string, string>  $labels
     * @return array{rows: array<int, array{status: string, label: string, count: int}>, totalCount: int}
     */
    private function creditStatusSummary(Builder $query, array $filters, array $labels): array
    {
        $counts = (clone $query)
            ->when(
                $filters['invoiceStart'],
                fn (Builder $inner) => $inner->where('credit_status_date', '>=', $filters['invoiceStart']->toDateString()),
            )
            ->when(
                $filters['invoiceEnd'],
                fn (Builder $inner) => $inner->where('credit_status_date', '<=', $filters['invoiceEnd']->toDateString()),
            )
            ->selectRaw('SUM(CASE WHEN credit_status = ? THEN 1 ELSE 0 END) as yes_count', [true])
            ->selectRaw('SUM(CASE WHEN credit_status = ? THEN 1 ELSE 0 END) as no_count', [false])
            ->first();

        $rows = [
            [
                'status' => 'yes',
                'label' => $labels['yes'] ?? 'Yes',
                'count' => (int) ($counts->yes_count ?? 0),
            ],
            [
                'status' => 'no',
                'label' => $labels['no'] ?? 'No',
                'count' => (int) ($counts->no_count ?? 0),
            ],
        ];

        return [
            'rows' => $rows,
            'totalCount' => (int) collect($rows)->sum('count'),
        ];
    }

    /** @return array<int, array{status: string, label: string, count: int, amount: float}> */
    private function claimsByStatus(Builder $query, array $workStatuses): array
    {
        $statuses = (clone $query)
            ->selectRaw('work_status_id as status_key')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM('.self::BALANCE_EXPRESSION.') as amount')
            ->groupBy('work_status_id')
            ->get()
            ->keyBy('status_key');

        return collect($workStatuses)
            ->map(function (array $status) use ($statuses): array {
                $row = $statuses->get($status['id']);

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

    private function applyServiceDateRange(Builder $query, ?Carbon $startDate, ?Carbon $endDate): void
    {
        $serviceDateExpression = 'COALESCE(service_date_start, date_of_service)';

        $query
            ->when($startDate, fn (Builder $inner) => $inner->whereDate(DB::raw($serviceDateExpression), '>=', $startDate->toDateString()))
            ->when($endDate, fn (Builder $inner) => $inner->whereDate(DB::raw($serviceDateExpression), '<=', $endDate->toDateString()));
    }

    /**
     * @return array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }
     */
    private function resolvePanelDateFilters(
        Request $request,
        string $prefix,
        bool $includeService = true,
    ): array {
        [$invoiceStart, $invoiceEnd] = $this->normalizedDateRange(
            $request->input("{$prefix}_invoice_start"),
            $request->input("{$prefix}_invoice_end"),
        );
        [$serviceStart, $serviceEnd] = $includeService
            ? $this->normalizedDateRange(
                $request->input("{$prefix}_service_start"),
                $request->input("{$prefix}_service_end"),
            )
            : [null, null];

        return [
            'invoiceStart' => $invoiceStart,
            'invoiceEnd' => $invoiceEnd,
            'serviceStart' => $serviceStart,
            'serviceEnd' => $serviceEnd,
        ];
    }

    /**
     * @param  array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }  $filters
     */
    private function applyPanelDateFilters(Builder $query, array $filters): void
    {
        $query
            ->when(
                $filters['invoiceStart'],
                fn (Builder $inner) => $inner->whereDate('cf_invoice_date', '>=', $filters['invoiceStart']->toDateString()),
            )
            ->when(
                $filters['invoiceEnd'],
                fn (Builder $inner) => $inner->whereDate('cf_invoice_date', '<=', $filters['invoiceEnd']->toDateString()),
            );

        $this->applyServiceDateRange($query, $filters['serviceStart'], $filters['serviceEnd']);
    }

    /** @return array{0: Carbon|null, 1: Carbon|null} */
    private function normalizedDateRange(mixed $start, mixed $end): array
    {
        $startDate = $this->parseDate($start);
        $endDate = $this->parseDate($end);

        if ($startDate && $endDate && $startDate->greaterThan($endDate)) {
            return [$endDate, $startDate];
        }

        return [$startDate, $endDate];
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
