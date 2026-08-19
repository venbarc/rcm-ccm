<?php

namespace App\Http\Controllers;

use App\Models\ClaimExport;
use App\Services\ClaimExportService;
use App\Services\ClaimFilterService;
use App\Support\CurrentAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimExportController extends Controller
{
    public function __construct(
        private readonly ClaimExportService $exports,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $pageFilterKeys = implode(',', ClaimFilterService::FILTER_KEYS);
        $validated = $request->validate([
            'filters' => ['nullable', "array:{$pageFilterKeys}"],
            'filters.search' => ['nullable', 'string', 'max:255'],
            'filters.modmed_claim_status' => ['nullable', 'string', 'max:255'],
            'filters.invoiced_status' => ['nullable', 'string', 'max:50'],
            'filters.credit_status' => ['nullable', 'string', 'max:50'],
            'filters.credit_reason' => ['nullable', 'string', 'max:255'],
            'filters.payer_name' => ['nullable', 'string', 'max:20000'],
            'filters.primary_provider' => ['nullable', 'string', 'max:255'],
            'filters.location' => ['nullable', 'string', 'max:255'],
            'filters.denial_reason' => ['nullable', 'string', 'max:255'],
            'filters.work_status' => ['nullable', 'string', 'max:100'],
            'filters.assigned_to' => ['nullable', 'string', 'max:30'],
            'filters.worked_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.worked_to' => ['nullable', 'date_format:Y-m-d'],
            'filters.service_date_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.service_date_to' => ['nullable', 'date_format:Y-m-d'],
            'filters.service_month' => ['nullable', 'date_format:Y-m'],
            'filters.cf_invoice_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.cf_invoice_to' => ['nullable', 'date_format:Y-m-d'],
            'filters.credit_status_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.credit_status_to' => ['nullable', 'date_format:Y-m-d'],
            'filters.procedure_code' => ['nullable', 'string', 'max:100'],
        ]);

        $export = $this->exports->startExport($account->value, $request->user(), $validated);

        return response()->json([
            'message' => 'The claims export has started.',
            'export' => $this->payload($export),
        ], 202);
    }

    public function active(Request $request): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $export = ClaimExport::query()
            ->with('user:id,name,email')
            ->where('account_type', $account->value)
            ->whereIn('status', ['queued', 'processing'])
            ->latest('id')
            ->first();

        return response()->json([
            'export' => $export ? $this->payload($export) : null,
        ]);
    }

    public function progress(Request $request, ClaimExport $claimExport): JsonResponse
    {
        $this->ensureAccountAccess($request, $claimExport);

        return response()->json([
            'export' => $this->payload($claimExport->fresh(['user:id,name,email'])),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $account = CurrentAccount::resolve($request);
        $exports = ClaimExport::query()
            ->with('user:id,name,email')
            ->where('account_type', $account->value)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->limit(20)
            ->get()
            ->map(fn (ClaimExport $export): array => $this->payload($export));

        return response()->json(['exports' => $exports]);
    }

    public function download(Request $request, ClaimExport $claimExport): StreamedResponse
    {
        $this->ensureAccountAccess($request, $claimExport);
        abort_unless($claimExport->status === 'completed', 409, 'The export is not ready.');
        abort_unless(Storage::exists($claimExport->file_path), 404, 'The export file no longer exists.');

        return Storage::download($claimExport->file_path, $claimExport->file_name);
    }

    private function ensureAccountAccess(Request $request, ClaimExport $claimExport): void
    {
        $account = CurrentAccount::resolve($request);
        abort_unless($claimExport->account_type === $account->value, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ClaimExport $export): array
    {
        $export->loadMissing('user:id,name,email');

        return [
            'id' => $export->id,
            'file_name' => $export->file_name,
            'status' => $export->status,
            'total_rows' => (int) $export->total_rows,
            'processed_rows' => (int) $export->processed_rows,
            'progress' => $export->total_rows > 0
                ? round(($export->processed_rows / $export->total_rows) * 100, 1)
                : 0,
            'error_message' => $export->error_message,
            'started_at' => $export->started_at?->toIso8601String(),
            'completed_at' => $export->completed_at?->toIso8601String(),
            'started_by' => $export->user?->only(['id', 'name', 'email']),
        ];
    }
}
