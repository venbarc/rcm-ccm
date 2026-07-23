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
    public const WORK_STATUSES = [
        'draft', 'paid', 'historical_posted_payments', 'rebilled', 'appeal',
        'pending', 'void', 'corrected', 'patient_balance',
    ];

    private const HEADERS = [
        'Claim ID', 'Bill ID', 'UID', 'Patient Name', 'Patient First Name',
        'Patient Last Name', 'Patient DOB', 'Patient MRN', 'Subscriber ID',
        'CPT Code', 'Modifiers', 'Units', 'Service Date Start', 'Service Date End',
        'Service Type', 'Diagnosis Code', 'Payer', 'Payer Category', 'Coverage Type',
        'Rendering Provider', 'Ordering Provider', 'Supervising Provider',
        'Practice Location', 'Location', 'Division', 'Place of Service Code',
        'Claim Status', 'Work Status', 'Priority', 'Assigned To', 'Denial Reason',
        'Notes', 'Source Notes', 'Charges', 'Historical Posted Payments',
        'New Payments', 'Adjustments', 'True Balance', 'Claimed Amount', 'Aging Days',
        'Activity Type', 'Batch User', 'Batch Name', 'Code Category',
        'Financial Category', 'Package Name', 'Posted Date', 'Transaction Date',
        'Primary Biller', 'Primary Biller Role', 'Primary Modifier',
        'Primary Provider Role', 'Quick Code', 'Recorded By',
    ];

    public function __construct(private readonly TeamService $teams) {}

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
            ->with('assignee:id,name,email')
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
        $row = [
            $claim->external_id,
            $claim->bill_id,
            $claim->uid,
            $claim->patient_name,
            $claim->first_name,
            $claim->last_name,
            $claim->patient_dob?->format('Y-m-d'),
            $claim->patient_id,
            $claim->subscriber_id,
            $claim->procedure_code ?: $claim->cpt_code,
            $claim->modifiers,
            $claim->units,
            $claim->service_date_start?->format('Y-m-d'),
            $claim->service_date_end?->format('Y-m-d'),
            $claim->service_type,
            $claim->diagnosis_code,
            $claim->payer_name ?: $claim->payer,
            $claim->payer_category,
            $claim->coverage_type,
            $claim->rendering_provider ?: $claim->provider,
            $claim->ordering_provider,
            $claim->supervising_provider,
            $claim->practice_location,
            $claim->location,
            $claim->division,
            $claim->place_of_service_code,
            $claim->claim_status,
            $claim->work_status ?: 'draft',
            $claim->priority,
            $claim->assignee?->name,
            $claim->denial_reason,
            $claim->notes,
            $claim->source_notes,
            $claim->true_charge ?? $claim->billed_amount,
            $claim->payments,
            $claim->new_payments,
            $claim->adjustments,
            $claim->true_balance ?? $claim->balance,
            $claim->claimed_amount,
            $claim->aging_days,
            $claim->activity_type,
            $claim->batch_user,
            $claim->batch_name,
            $claim->code_category,
            $claim->financial_category,
            $claim->package_name,
            $claim->posted_date?->format('Y-m-d'),
            $claim->transaction_date?->format('Y-m-d'),
            $claim->primary_biller,
            $claim->primary_biller_role,
            $claim->primary_modifier,
            $claim->primary_provider_role,
            $claim->quick_code,
            $claim->recorded_by,
        ];

        return array_map($this->sanitizeCsvValue(...), $row);
    }

    private function sanitizeCsvValue(mixed $value): bool|float|int|string|null
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^\s*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
