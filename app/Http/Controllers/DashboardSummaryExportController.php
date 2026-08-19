<?php

namespace App\Http\Controllers;

use App\Services\DashboardSummaryService;
use App\Support\BusinessTime;
use App\Support\ClaimWorkspace;
use App\Support\CurrentAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardSummaryExportController extends Controller
{
    public const PANELS = ['invoiced-summary', 'credit-status-summary'];

    public function __construct(private readonly DashboardSummaryService $summaries) {}

    /**
     * Downloads a dashboard summary panel exactly as the panel renders it: same
     * date filters, same grouping, same grand total.
     */
    public function __invoke(Request $request, string $panel): StreamedResponse
    {
        abort_unless(in_array($panel, self::PANELS, true), 404);
        $account = CurrentAccount::resolve($request);
        $procedureLabel = ClaimWorkspace::isPrinciple($account->value) ? 'Procedure Code' : 'CPT Code';

        [$filters, $rows, $dateRange] = $panel === 'invoiced-summary'
            ? $this->invoicedSummaryRows($request, $account->value, $procedureLabel)
            : $this->creditStatusSummaryRows($request, $account->value, $procedureLabel);

        return $this->download($rows, $this->fileName($account->value, $panel, $filters, $dateRange));
    }

    /**
     * @return array{0: array<string, Carbon|null>, 1: array<int, array<int, string|float>>, 2: array{0: string, 1: string}}
     */
    private function invoicedSummaryRows(Request $request, string $account, string $procedureLabel): array
    {
        $filters = $this->summaries->invoicedPanelFilters($request, $account);
        $summary = $this->summaries->invoicedSummary(
            $this->summaries->invoicedSummaryQuery($account, $filters),
        );

        $rows = [[$procedureLabel, 'Units']];
        foreach ($summary['rows'] as $row) {
            $rows[] = [$row['cpt'] ?? "No {$procedureLabel}", $row['units']];
        }
        $rows[] = ['Grand Total', $summary['totalUnits']];

        return [
            $filters,
            $rows,
            ClaimWorkspace::supports($account, 'cf_invoice_date')
                ? ['invoiceStart', 'invoiceEnd']
                : ['serviceStart', 'serviceEnd'],
        ];
    }

    /**
     * @return array{0: array<string, Carbon|null>, 1: array<int, array<int, string|float|int>>, 2: array{0: string, 1: string}}
     */
    private function creditStatusSummaryRows(Request $request, string $account, string $procedureLabel): array
    {
        $filters = $this->summaries->creditStatusPanelFilters($request);
        $summary = $this->summaries->creditStatusSummary(
            $this->summaries->creditStatusSummaryQuery($account, $filters),
        );

        $rows = [[
            $procedureLabel,
            'Count of CPT',
            'Sum of Units',
            'Sum of True Charge',
            'Sum of CF Invoice Amount',
        ]];
        foreach ($summary['rows'] as $row) {
            $rows[] = [
                $row['cpt'] ?? "No {$procedureLabel}",
                $row['count'],
                $row['units'],
                $row['trueCharge'],
                $row['cfInvoiceAmount'],
            ];
        }
        $rows[] = [
            'Grand Total',
            $summary['totalCount'],
            $summary['totalUnits'],
            $summary['totalTrueCharge'],
            $summary['totalCfInvoiceAmount'],
        ];

        return [$filters, $rows, ['invoiceStart', 'invoiceEnd']];
    }

    /**
     * @param  array<int, array<int, string|float|int>>  $rows
     */
    private function download(array $rows, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->sanitizeCsvValue(...), $row), ',', '"', '\\');
            }

            fclose($stream);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  array<string, Carbon|null>  $filters
     * @param  array{0: string, 1: string}  $dateRange
     */
    private function fileName(string $account, string $panel, array $filters, array $dateRange): string
    {
        $start = $filters[$dateRange[0]]?->toDateString();
        $end = $filters[$dateRange[1]]?->toDateString();
        $range = match (true) {
            $start !== null && $end !== null => "{$start}_to_{$end}",
            $start !== null => "from_{$start}",
            $end !== null => "through_{$end}",
            default => 'all_dates',
        };

        return $account.'_'.str_replace('-', '_', $panel).'_'.$range.'_'.BusinessTime::now()->format('Y-m-d_His').'.csv';
    }

    private function sanitizeCsvValue(mixed $value): bool|float|int|string|null
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^\s*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
