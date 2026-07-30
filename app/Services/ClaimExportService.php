<?php

namespace App\Services;

use App\Jobs\FinalizeClaimExport;
use App\Jobs\ProcessClaimExportChunk;
use App\Models\Claim;
use App\Models\ClaimExport;
use App\Models\ClaimImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ClaimExportService
{
    private const HEADERS = [
        'CPT', 'Location', 'Bill ID', 'Invoice Rate Per Unit', 'CF Invoice Amount',
        'Payments', 'True Balance', 'True Charge', 'Units', 'BillingID-CPT',
        'Charges', 'ModMed_Claim_Status', 'CF Invoice Date', 'Patient DOB',
        'Patient First Name', 'Patient Last Name', 'Patient MRN', 'Patient Name',
        'Payer', 'Payer-CPT', 'Place of Service Code', 'Posted Date Month/Year',
        'Primary Provider', 'Service Date', 'True Charge Per Unit',
        'Work Status', 'Assigned To', 'Denial Reason', 'Notes',
        'Invoiced Status', 'Invoiced Status Date', 'Credit Status',
        'Credit Status Date', 'Credit Reason', 'Last Updated',
    ];

    private const INVOICED_STATUS_LABELS = [
        'invoiced' => 'Invoiced',
    ];

    /** @var array<string, array<string, string>> */
    private array $configurationLabels = [];

    public function __construct(
        private readonly ClaimConfigurationService $configurations,
        private readonly TeamService $teams,
    ) {}

    /**
     * @param  array{type:string,status?:string|null,assigned_to?:string|null}  $filters
     */
    public function startExport(string $account, User $user, array $filters): ClaimExport
    {
        if (ClaimImport::query()
            ->where('account_type', $account)
            ->whereIn('status', ['queued', 'processing'])
            ->exists()) {
            throw ValidationException::withMessages([
                'export' => 'Cannot export while a claims import is processing.',
            ]);
        }

        if (ClaimExport::query()
            ->where('account_type', $account)
            ->whereIn('status', ['queued', 'processing'])
            ->exists()) {
            throw ValidationException::withMessages([
                'export' => 'An export is already running for this account.',
            ]);
        }

        $normalizedFilters = $this->normalizeFilters($account, $user, $filters);
        $totalRows = $this->buildQuery($account, $normalizedFilters)->count();

        if ($totalRows === 0) {
            throw ValidationException::withMessages([
                'export' => 'No claims match the selected export.',
            ]);
        }

        $chunkSize = max((int) config('claims.export.chunk_size', 1000), 1);
        $totalChunks = (int) ceil($totalRows / $chunkSize);
        $prefix = match ($normalizedFilters['type']) {
            'status' => Str::slug((string) $normalizedFilters['status'], '_').'_',
            'assignee' => 'assigned_',
            default => '',
        };
        $fileName = $prefix.'claims_export_'.now()->format('Y-m-d_His').'.csv';
        $filePath = 'claim-exports/'.Str::slug($account).'/'.Str::uuid().'_'.$fileName;

        Storage::makeDirectory(dirname($filePath));
        $this->writeHeaders($filePath);

        $export = null;

        try {
            $export = ClaimExport::query()->create([
                'account_type' => $account,
                'user_id' => $user->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'status' => 'queued',
                'total_rows' => $totalRows,
                'total_chunks' => $totalChunks,
                'filters' => $normalizedFilters,
            ]);

            $jobs = [];
            for ($chunk = 1; $chunk <= $totalChunks; $chunk++) {
                $jobs[] = new ProcessClaimExportChunk($export->id, $chunk);
            }
            $jobs[] = new FinalizeClaimExport($export->id);

            Bus::chain($jobs)->dispatch();

            return $export->fresh();
        } catch (Throwable $exception) {
            if ($export) {
                $this->failExport($export->id, $exception->getMessage());
            } else {
                Storage::delete($filePath);
            }

            throw $exception;
        }
    }

    public function processChunk(int $exportId, int $chunkNumber): void
    {
        $export = ClaimExport::query()->find($exportId);
        if (! $export || $export->status === 'failed') {
            return;
        }

        if ($export->status === 'queued') {
            $export->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
        }

        $chunkSize = max((int) config('claims.export.chunk_size', 1000), 1);
        $claims = $this->buildQuery($export->account_type, $export->filters ?? [])
            ->with(['assignee:id,name,email', 'rawRow:id,claim_id,raw_payload'])
            ->orderBy('id')
            ->forPage($chunkNumber, $chunkSize)
            ->get();

        $handle = fopen(Storage::path($export->file_path), 'ab');
        if ($handle === false) {
            throw new RuntimeException('The export file could not be opened.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('The export file could not be locked.');
            }

            foreach ($claims as $claim) {
                fputcsv($handle, $this->formatRow($claim), ',', '"', '\\');
            }

            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        DB::transaction(function () use ($exportId, $claims): void {
            $lockedExport = ClaimExport::query()->lockForUpdate()->find($exportId);
            if (! $lockedExport || $lockedExport->status === 'failed') {
                return;
            }

            $lockedExport->update([
                'processed_rows' => min($lockedExport->processed_rows + $claims->count(), $lockedExport->total_rows),
                'processed_chunks' => min($lockedExport->processed_chunks + 1, $lockedExport->total_chunks),
            ]);
        });
    }

    public function finalizeExport(int $exportId): void
    {
        DB::transaction(function () use ($exportId): void {
            $export = ClaimExport::query()->lockForUpdate()->find($exportId);
            if (! $export || $export->status === 'failed') {
                return;
            }

            if ($export->processed_chunks !== $export->total_chunks) {
                throw new RuntimeException('The export did not finish every chunk.');
            }

            $export->update([
                'status' => 'completed',
                'processed_rows' => $export->total_rows,
                'completed_at' => now(),
            ]);
        });
    }

    public function failExport(int $exportId, string $message): void
    {
        $export = ClaimExport::query()->find($exportId);
        if (! $export || $export->status === 'completed') {
            return;
        }

        Storage::delete($export->file_path);
        $export->update([
            'status' => 'failed',
            'error_message' => Str::limit($message, 1000, ''),
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function buildQuery(string $account, array $filters): Builder
    {
        return Claim::query()
            ->where('account_type', $account)
            ->when(
                ($filters['type'] ?? 'all') === 'status',
                fn (Builder $query) => $query->where('work_status', $filters['status']),
            )
            ->when(
                ($filters['type'] ?? 'all') === 'assignee' && ($filters['assigned_to'] ?? null) === 'unassigned',
                fn (Builder $query) => $query->whereNull('assigned_to'),
            )
            ->when(
                ($filters['type'] ?? 'all') === 'assignee' && is_numeric($filters['assigned_to'] ?? null),
                fn (Builder $query) => $query->where('assigned_to', (int) $filters['assigned_to']),
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type:string,status:?string,assigned_to:?string}
     */
    private function normalizeFilters(string $account, User $user, array $filters): array
    {
        $type = (string) ($filters['type'] ?? 'all');
        $status = $type === 'status' ? (string) ($filters['status'] ?? '') : null;
        $assignedTo = $type === 'assignee' ? (string) ($filters['assigned_to'] ?? '') : null;

        if ($type === 'assignee') {
            if (! $user->canAssignClaims()) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'You do not have permission to export by assignee.',
                ]);
            }

            if ($assignedTo !== 'unassigned') {
                $candidateIds = $this->teams->assignmentCandidates($user, $account)->modelKeys();
                if (! is_numeric($assignedTo) || ! in_array((int) $assignedTo, $candidateIds, true)) {
                    throw ValidationException::withMessages([
                        'assigned_to' => 'Choose an assignee from your active account team.',
                    ]);
                }
            }
        }

        return [
            'type' => $type,
            'status' => $status,
            'assigned_to' => $assignedTo,
        ];
    }

    private function writeHeaders(string $filePath): void
    {
        $handle = fopen(Storage::path($filePath), 'wb');
        if ($handle === false) {
            throw new RuntimeException('The export file could not be created.');
        }

        try {
            fputcsv($handle, self::HEADERS, ',', '"', '\\');
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, bool|float|int|string|null>
     */
    private function formatRow(Claim $claim): array
    {
        $procedureCode = $claim->procedure_code ?: $claim->cpt_code;
        $payer = $claim->payer_name ?: $claim->payer;

        $row = [
            $procedureCode,
            $claim->location,
            $claim->bill_id,
            $this->sourceValue($claim, 'invoice_rate_per_unit'),
            $claim->cf_invoice_amount,
            $claim->payments,
            $claim->true_balance ?? $claim->balance,
            $claim->true_charge ?? $claim->billed_amount,
            $claim->units,
            $this->sourceValue($claim, 'billingid_cpt', filled($claim->bill_id) && filled($procedureCode) ? "{$claim->bill_id}-{$procedureCode}" : null),
            $this->sourceValue($claim, 'charges'),
            $this->configurationLabel(
                $claim->account_type,
                ClaimConfigurationService::MODMED_CLAIM_STATUS,
                $claim->modmed_claim_status,
            ),
            $claim->cf_invoice_date?->format('Y-m-d'),
            $claim->patient_dob?->format('Y-m-d'),
            $claim->first_name,
            $claim->last_name,
            $claim->patient_id,
            $claim->patient_name,
            $payer,
            $this->sourceValue($claim, 'payer_cpt', filled($payer) && filled($procedureCode) ? "{$payer}-{$procedureCode}" : null),
            $claim->place_of_service_code,
            $claim->posted_date?->format('Y-m-d'),
            $claim->primary_provider ?: $claim->provider,
            ($claim->service_date_start ?? $claim->date_of_service)?->format('Y-m-d'),
            $this->sourceValue($claim, 'true_charge_per_unit'),
            $this->configurationLabel($claim->account_type, ClaimConfigurationService::WORK_STATUS, $claim->work_status ?: 'draft'),
            $claim->assignee?->name,
            $this->configurationLabel($claim->account_type, ClaimConfigurationService::DENIAL_REASON, $claim->denial_reason),
            $claim->notes,
            self::INVOICED_STATUS_LABELS['invoiced'],
            $claim->cf_invoice_date?->format('Y-m-d'),
            match ($claim->credit_status) {
                true => $this->configurationLabel($claim->account_type, ClaimConfigurationService::CREDIT_STATUS, 'yes'),
                false => $this->configurationLabel($claim->account_type, ClaimConfigurationService::CREDIT_STATUS, 'no'),
                null => '--',
            },
            $claim->credit_status_date?->format('Y-m-d'),
            $this->configurationLabel($claim->account_type, ClaimConfigurationService::CREDIT_REASON, $claim->credit_reason),
            $claim->updated_at?->format('Y-m-d H:i:s'),
        ];

        return array_map($this->sanitizeCsvValue(...), $row);
    }

    private function sourceValue(Claim $claim, string $key, mixed $fallback = null): mixed
    {
        $value = $claim->rawRow?->raw_payload[$key] ?? null;

        return $value === null || $value === '' ? $fallback : $value;
    }

    private function configurationLabel(string $account, string $type, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cacheKey = "{$account}:{$type}";
        $this->configurationLabels[$cacheKey] ??= $this->configurations->labelMap($account, $type);

        return $this->configurationLabels[$cacheKey][$value] ?? $value;
    }

    private function sanitizeCsvValue(mixed $value): bool|float|int|string|null
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^\s*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
