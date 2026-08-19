<?php

namespace App\Services;

use App\Models\Claim;
use App\Support\ClaimWorkspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared source of truth for the dashboard summary panels. The dashboard page and
 * the panel exports both build their queries here so an export can never drift
 * away from the numbers rendered on screen.
 */
class DashboardSummaryService
{
    public const CPT_EXPRESSION = "COALESCE(NULLIF(TRIM(procedure_code), ''), NULLIF(TRIM(cpt_code), ''))";

    private const TRUE_CHARGE_EXPRESSION = 'COALESCE(true_charge, billed_amount, 0)';

    private const CF_INVOICE_AMOUNT_EXPRESSION = 'COALESCE(cf_invoice_amount, 0)';

    /**
     * @return array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }
     */
    public function panelDateFilters(
        Request $request,
        string $prefix,
        bool $includeService = true,
        bool $includeInvoice = true,
    ): array {
        [$invoiceStart, $invoiceEnd] = $includeInvoice
            ? $this->normalizedDateRange(
                $request->input("{$prefix}_invoice_start"),
                $request->input("{$prefix}_invoice_end"),
            )
            : [null, null];
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
     * Principle has no CF invoice date, so its invoiced panel filters on service dates.
     *
     * @return array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }
     */
    public function invoicedPanelFilters(Request $request, string $account): array
    {
        $hasCfInvoiceDate = ClaimWorkspace::supports($account, 'cf_invoice_date');

        return $this->panelDateFilters(
            $request,
            'invoiced',
            includeService: ! $hasCfInvoiceDate,
            includeInvoice: $hasCfInvoiceDate,
        );
    }

    /**
     * @return array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }
     */
    public function creditStatusPanelFilters(Request $request): array
    {
        return $this->panelDateFilters($request, 'credit_status', false);
    }

    /**
     * @param  array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }  $filters
     */
    public function invoicedSummaryQuery(string $account, array $filters): Builder
    {
        $query = Claim::query()
            ->where('account_type', $account)
            ->whereNotNull(ClaimWorkspace::supports($account, 'cf_invoice_date') ? 'cf_invoice_date' : 'cf_invoice_amount');

        $this->applyPanelDateFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array{
     *     invoiceStart: Carbon|null,
     *     invoiceEnd: Carbon|null,
     *     serviceStart: Carbon|null,
     *     serviceEnd: Carbon|null
     * }  $filters
     */
    public function creditStatusSummaryQuery(string $account, array $filters): Builder
    {
        return Claim::query()
            ->where('account_type', $account)
            ->where('credit_status', true)
            ->when(
                $filters['invoiceStart'],
                fn (Builder $inner) => $inner->where('credit_status_date', '>=', $filters['invoiceStart']->toDateString()),
            )
            ->when(
                $filters['invoiceEnd'],
                fn (Builder $inner) => $inner->where('credit_status_date', '<=', $filters['invoiceEnd']->toDateString()),
            );
    }

    /** @return array{rows: array<int, array{cpt: string|null, units: float}>, totalUnits: float} */
    public function invoicedSummary(Builder $query): array
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
     * @return array{
     *     rows: array<int, array{cpt: string|null, count: int, units: float, trueCharge: float, cfInvoiceAmount: float}>,
     *     totalCount: int,
     *     totalUnits: float,
     *     totalTrueCharge: float,
     *     totalCfInvoiceAmount: float
     * }
     */
    public function creditStatusSummary(Builder $query): array
    {
        $rows = (clone $query)
            ->selectRaw(self::CPT_EXPRESSION.' as cpt')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('SUM(COALESCE(units, 0)) as units')
            ->selectRaw('SUM('.self::TRUE_CHARGE_EXPRESSION.') as true_charge')
            ->selectRaw('SUM('.self::CF_INVOICE_AMOUNT_EXPRESSION.') as cf_invoice_amount')
            ->groupByRaw(self::CPT_EXPRESSION)
            ->orderByDesc('line_count')
            ->orderBy('cpt')
            ->get()
            ->map(fn ($row): array => [
                'cpt' => filled($row->cpt) ? (string) $row->cpt : null,
                'count' => (int) ($row->line_count ?? 0),
                'units' => (float) ($row->units ?? 0),
                'trueCharge' => (float) ($row->true_charge ?? 0),
                'cfInvoiceAmount' => (float) ($row->cf_invoice_amount ?? 0),
            ])
            ->all();

        return [
            'rows' => $rows,
            'totalCount' => (int) collect($rows)->sum('count'),
            'totalUnits' => (float) collect($rows)->sum('units'),
            'totalTrueCharge' => (float) collect($rows)->sum('trueCharge'),
            'totalCfInvoiceAmount' => (float) collect($rows)->sum('cfInvoiceAmount'),
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
    public function applyPanelDateFilters(Builder $query, array $filters): void
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

    public function applyServiceDateRange(Builder $query, ?Carbon $startDate, ?Carbon $endDate): void
    {
        $serviceDateExpression = 'COALESCE(service_date_start, date_of_service)';

        $query
            ->when($startDate, fn (Builder $inner) => $inner->whereDate(DB::raw($serviceDateExpression), '>=', $startDate->toDateString()))
            ->when($endDate, fn (Builder $inner) => $inner->whereDate(DB::raw($serviceDateExpression), '<=', $endDate->toDateString()));
    }

    /** @return array{0: Carbon|null, 1: Carbon|null} */
    public function normalizedDateRange(mixed $start, mixed $end): array
    {
        $startDate = $this->parseDate($start);
        $endDate = $this->parseDate($end);

        if ($startDate && $endDate && $startDate->greaterThan($endDate)) {
            return [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    public function parseDate(mixed $value): ?Carbon
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
