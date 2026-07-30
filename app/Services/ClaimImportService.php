<?php

namespace App\Services;

use App\Imports\ChunkReadFilter;
use App\Jobs\FinalizeClaimImport;
use App\Jobs\ProcessClaimImportChunk;
use App\Models\Claim;
use App\Models\ClaimExport;
use App\Models\ClaimImport;
use App\Models\ClaimImportSnapshot;
use App\Models\ClaimRawRow;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class ClaimImportService
{
    /** @var array<string, array<int, string>> */
    private const FIELD_ALIASES = [
        'first_name' => ['Patient First Name', 'First Name'],
        'last_name' => ['Patient Last Name', 'Last Name'],
        'patient_name' => ['Patient Name', 'Patient'],
        'procedure_code' => ['CPT/Product', 'Procedure Code', 'CPT Code', 'CPT'],
        'service_date_start' => ['Service Date', 'Service Date 1', 'DOS'],
        'service_date_end' => ['Service Date End'],
        'true_charge' => ['Charges', 'True Charge'],
        'true_balance' => ['True Balance'],
        'payments' => ['Historical Posted Payments', 'Payments'],
        'new_payments' => ['New Payments'],
        'bill_id' => ['Bill ID'],
        'modmed_claim_status' => ['ModMed_Claim_Status', 'ModMed Claim Status'],
        'cf_invoice_date' => ['CF Invoice Date'],
        'cf_invoice_amount' => ['CF Invoice Amount'],
        'activity_type' => ['Activity Type'],
        'batch_user' => ['Batch User'],
        'batch_name' => ['Batch Name'],
        'external_id' => ['Claim ID', 'Claim Id', 'Claim Number'],
        'code_category' => ['Code Category'],
        'coverage_type' => ['Coverage Type'],
        'division' => ['Division'],
        'financial_category' => ['Financial Category'],
        'location' => ['Location'],
        'source_notes' => ['Notes'],
        'ordering_provider' => ['Ordering Provider'],
        'package_name' => ['Package Name'],
        'patient_dob' => ['Patient DOB', 'Patient Dob'],
        'patient_id' => ['Patient MRN', 'Patient ID', 'Patient Id'],
        'payer_name' => ['Payer', 'Payer Name'],
        'place_of_service_code' => ['Place of Service Code'],
        'posted_date' => ['Posted Date', 'Posted Date Month/Year'],
        'practice_location' => ['Practice Location'],
        'primary_biller' => ['Primary Biller'],
        'primary_biller_role' => ['Primary Biller Role'],
        'primary_modifier' => ['Primary Modifier'],
        'primary_provider' => ['Primary Provider'],
        'rendering_provider' => ['Rendering Provider'],
        'primary_provider_role' => ['Primary Provider Role'],
        'quick_code' => ['Quick Code'],
        'recorded_by' => ['Recorded By'],
        'supervising_provider' => ['Supervising Provider'],
        'transaction_date' => ['Transaction Date'],
        'cpt_code' => ['CPT Code'],
        'uid' => ['UID'],
        'adjustments' => ['Adjustments'],
        'aging_days' => ['Aging Days'],
        'claimed_amount' => ['Claimed Amount'],
        'diagnosis_code' => ['Diagnosis Code'],
        'modifiers' => ['Modifiers'],
        'payer_category' => ['Payer Category'],
        'service_type' => ['Service Type', 'Serivce Type'],
        'subscriber_id' => ['Subscriber ID', 'Subscriber Id'],
        'units' => ['Units'],
        'claim_status' => ['Claim Status', 'Claims Status'],
        'denial_reason' => ['Denial Reason'],
    ];

    /** @var array<int, string> */
    private const IMPORTED_FIELDS = [
        'uid', 'bill_id', 'payer_name', 'cpt_code', 'primary_provider', 'rendering_provider', 'payments',
        'new_payments', 'true_balance', 'true_charge', 'adjustments', 'aging_days',
        'claim_status', 'modmed_claim_status', 'cf_invoice_date', 'cf_invoice_amount', 'claimed_amount',
        'diagnosis_code', 'first_name', 'last_name',
        'modifiers', 'patient_dob', 'patient_id', 'payer_category', 'procedure_code',
        'service_type', 'service_date_start', 'service_date_end', 'subscriber_id', 'units',
        'activity_type', 'batch_user', 'batch_name', 'code_category', 'coverage_type',
        'division', 'financial_category', 'location', 'source_notes', 'ordering_provider',
        'package_name', 'place_of_service_code', 'posted_date', 'practice_location',
        'primary_biller', 'primary_biller_role', 'primary_modifier',
        'primary_provider_role', 'quick_code', 'recorded_by', 'supervising_provider',
        'transaction_date',
    ];

    /** @var array<int, string> */
    private const DECIMAL_FIELDS = [
        'payments', 'new_payments', 'true_balance', 'true_charge', 'adjustments',
        'claimed_amount', 'units', 'cf_invoice_amount',
    ];

    /** @var array<int, string> */
    private const DATE_FIELDS = [
        'patient_dob', 'service_date_start', 'service_date_end', 'posted_date',
        'transaction_date', 'cf_invoice_date',
    ];

    public function __construct(
        private readonly ClaimActivityService $activities,
        private readonly ClaimConfigurationService $configurations,
    ) {}

    public function queue(UploadedFile $file, string $account, User $user): ClaimImport
    {
        if (ClaimExport::query()
            ->where('account_type', $account)
            ->whereIn('status', ['queued', 'processing'])
            ->exists()) {
            throw ValidationException::withMessages([
                'file' => 'Cannot import claims while an export is processing.',
            ]);
        }

        $storedPath = $file->store('claim-imports');

        try {
            [$headers, $totalRows] = $this->inspect(Storage::path($storedPath));
            $this->validateHeaders($headers);
            if ($totalRows === 0) {
                throw new RuntimeException('The Tricity file does not contain any claim rows.');
            }
        } catch (Throwable $exception) {
            Storage::delete($storedPath);
            throw $exception;
        }

        $chunkSize = max(1, (int) config('claims.import.chunk_size', 500));
        $totalChunks = max(1, (int) ceil($totalRows / $chunkSize));
        $import = ClaimImport::create([
            'account_type' => $account,
            'file_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'processing',
            'total_rows' => $totalRows,
            'total_chunks' => $totalChunks,
            'imported_by' => $user->id,
            'started_at' => now(),
        ]);

        $jobs = [];
        for ($chunk = 1; $chunk <= $totalChunks; $chunk++) {
            $jobs[] = new ProcessClaimImportChunk($import->id, $chunk);
        }
        $jobs[] = new FinalizeClaimImport($import->id);

        Bus::chain($jobs)->dispatch();

        return $import->fresh();
    }

    public function processChunk(ClaimImport $import, int $chunkNumber): void
    {
        if (function_exists('ini_set')) {
            ini_set('memory_limit', config('claims.import.memory_limit', 512).'M');
        }

        [$headers, $rows, $startRow] = $this->readChunk($import, $chunkNumber);
        $columnMap = $this->mapColumns($headers);
        $this->validateHeaders($headers);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($import, $headers, $rows, $columnMap, &$created, &$updated, &$skipped): void {
            $rawHeaderKeys = $this->rawHeaderKeys($headers);

            foreach ($rows as $row) {
                $data = $this->mapRow($row, $columnMap);
                if ($this->isEmptyRow($data)) {
                    $skipped++;

                    continue;
                }

                $billId = $this->string($data['bill_id'] ?? null);
                if ($billId === null) {
                    $skipped++;

                    continue;
                }

                $payload = $this->normalizePayload($data);
                $payload['denial_reason'] = $this->configurations->resolveDenialReason(
                    $import->account_type,
                    $payload['denial_reason'] ?? null,
                );
                $sourceHash = $this->sourceHash($billId, $payload);
                $claim = $this->findExistingClaim($import, $billId, $sourceHash, $payload)
                    ?? new Claim(['account_type' => $import->account_type]);
                $isNew = ! $claim->exists;

                if (! $isNew && (int) $claim->last_import_id !== $import->id) {
                    ClaimImportSnapshot::query()->firstOrCreate(
                        ['claim_import_id' => $import->id, 'claim_id' => $claim->id],
                        ['snapshot_data' => [
                            'claim' => $claim->getOriginal(),
                            'raw_payload' => $claim->rawRow?->raw_payload,
                        ]],
                    );
                }

                $patientName = $this->string($data['patient_name'] ?? null)
                    ?? trim(implode(', ', array_filter([
                        $payload['last_name'] ?? null,
                        $payload['first_name'] ?? null,
                    ])))
                    ?: $billId;

                $existingGroupOwner = $isNew
                    ? Claim::query()
                        ->where('account_type', $import->account_type)
                        ->where('bill_id', $billId)
                        ->whereNotNull('assigned_to')
                        ->latest('updated_at')
                        ->first(['assigned_to', 'status', 'priority'])
                    : null;

                $wasWorkedOrModified = ! $isNew && $this->wasWorkedOrModified($claim);
                $managed = $isNew || ! $wasWorkedOrModified ? [
                    'work_status' => 'draft',
                    'work_status_manually_set' => false,
                    'denial_reason' => $payload['denial_reason'] ?? null,
                    'notes' => null,
                    'assigned_to' => $isNew ? $existingGroupOwner?->assigned_to : $claim->assigned_to,
                    'status' => $isNew ? ($existingGroupOwner?->status ?? 'new') : $claim->status,
                    'priority' => $isNew ? ($existingGroupOwner?->priority ?? 'normal') : $claim->priority,
                ] : $claim->only([
                    'work_status', 'work_status_manually_set', 'denial_reason', 'notes',
                    'assigned_to', 'priority', 'status',
                ]);

                $claim->fill([
                    ...$payload,
                    ...$managed,
                    'source_hash' => $sourceHash,
                    'external_id' => $billId,
                    'patient_name' => $patientName,
                    'date_of_service' => $payload['service_date_start'],
                    'payer' => $payload['payer_name'],
                    'provider' => $payload['primary_provider'],
                    'cpt_code' => $payload['cpt_code'] ?? $payload['procedure_code'],
                    'last_import_id' => $import->id,
                ])->save();

                ClaimRawRow::query()->updateOrCreate(
                    ['claim_id' => $claim->id],
                    [
                        'claim_import_id' => $import->id,
                        'raw_payload' => $this->rawPayload($row, $rawHeaderKeys),
                    ],
                );

                $isNew ? $created++ : $updated++;
            }

            ClaimImport::query()->whereKey($import->id)->update([
                'processed_rows' => DB::raw('processed_rows + '.count($rows)),
                'processed_chunks' => DB::raw('processed_chunks + 1'),
                'created_count' => DB::raw("created_count + {$created}"),
                'updated_count' => DB::raw("updated_count + {$updated}"),
                'skipped_count' => DB::raw("skipped_count + {$skipped}"),
            ]);
        });
    }

    public function finalizeImport(int $importId): void
    {
        $import = DB::transaction(function () use ($importId): ?ClaimImport {
            $import = ClaimImport::query()->lockForUpdate()->find($importId);
            if (! $import || $import->status === 'failed') {
                return null;
            }

            if ($import->processed_chunks < $import->total_chunks) {
                throw new RuntimeException('The import finished before every chunk was processed.');
            }

            Claim::query()
                ->where('account_type', $import->account_type)
                ->where(fn ($query) => $query->whereNull('last_import_id')->orWhere('last_import_id', '!=', $import->id))
                ->delete();

            ClaimImportSnapshot::query()->where('claim_import_id', $import->id)->delete();
            $import->update(['status' => 'completed', 'completed_at' => now()]);

            return $import->fresh('importer');
        });

        if ($import) {
            $this->deleteImportSourceFile($import);

            $this->activities->record(
                $import->account_type,
                'import',
                "Imported {$import->file_name}",
                $import->importer,
                after: [
                    'created' => $import->created_count,
                    'updated' => $import->updated_count,
                    'skipped' => $import->skipped_count,
                ],
            );
        }
    }

    public function failImport(int $importId, string $message): void
    {
        $import = DB::transaction(function () use ($importId, $message): ?ClaimImport {
            $import = ClaimImport::query()->lockForUpdate()->find($importId);
            if (! $import || $import->status === 'completed') {
                return null;
            }

            $snapshots = ClaimImportSnapshot::query()
                ->where('claim_import_id', $importId)
                ->get();
            $restoredIds = [];

            foreach ($snapshots as $snapshot) {
                $snapshotData = $snapshot->snapshot_data ?? [];
                $claimData = $snapshotData['claim'] ?? [];
                unset($claimData['id'], $claimData['created_at'], $claimData['updated_at']);

                if ($snapshot->claim_id && $claimData !== []) {
                    Claim::query()->whereKey($snapshot->claim_id)->update($claimData);
                    $restoredIds[] = $snapshot->claim_id;

                    if (array_key_exists('raw_payload', $snapshotData) && $snapshotData['raw_payload'] !== null) {
                        ClaimRawRow::query()->updateOrCreate(
                            ['claim_id' => $snapshot->claim_id],
                            ['claim_import_id' => $claimData['last_import_id'] ?? null, 'raw_payload' => $snapshotData['raw_payload']],
                        );
                    } else {
                        ClaimRawRow::query()->where('claim_id', $snapshot->claim_id)->delete();
                    }
                }
            }

            Claim::query()
                ->where('last_import_id', $importId)
                ->when($restoredIds !== [], fn ($query) => $query->whereNotIn('id', $restoredIds))
                ->delete();
            ClaimImportSnapshot::query()->where('claim_import_id', $importId)->delete();

            $import->update([
                'status' => 'failed',
                'failed_count' => max(1, $import->failed_count),
                'error_message' => Str::limit($message, 2000),
                'completed_at' => now(),
            ]);

            return $import->fresh();
        });

        if ($import) {
            $this->deleteImportSourceFile($import);
        }
    }

    private function deleteImportSourceFile(ClaimImport $import): void
    {
        if ($import->stored_path && (! Storage::exists($import->stored_path) || Storage::delete($import->stored_path))) {
            $import->forceFill(['stored_path' => null])->saveQuietly();
        }
    }

    private function wasWorkedOrModified(Claim $claim): bool
    {
        if ($claim->work_status_manually_set
            || filled($claim->notes)
            || filled($claim->denial_reason)
            || $claim->credit_status !== null
            || filled($claim->credit_reason)) {
            return true;
        }

        return $claim->activities()
            ->whereNotNull('user_id')
            ->where('action', '!=', 'assigned')
            ->exists();
    }

    /** @return array{0: array<int, mixed>, 1: int} */
    private function inspect(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'csv' || $extension === 'txt') {
            $file = new \SplFileObject($path, 'r');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $headers = $file->fgetcsv() ?: [];
            $rows = 0;
            while (! $file->eof()) {
                $row = $file->fgetcsv();
                if (is_array($row) && $row !== [null] && ! $this->isEmptyRow($row)) {
                    $rows++;
                }
            }

            return [$headers, $rows];
        }

        $reader = IOFactory::createReaderForFile($path);
        $info = $reader->listWorksheetInfo($path)[0] ?? null;
        if (! $info) {
            throw new RuntimeException('The workbook does not contain a worksheet.');
        }

        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new ChunkReadFilter(1, 1));
        $sheet = $reader->load($path)->getActiveSheet();
        $headers = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1', null, true, false)[0] ?? [];

        return [$headers, max(0, ((int) $info['totalRows']) - 1)];
    }

    /** @return array{0: array<int, mixed>, 1: array<int, array<int, mixed>>, 2: int} */
    private function readChunk(ClaimImport $import, int $chunkNumber): array
    {
        $path = Storage::path($import->stored_path);
        $chunkSize = max(1, (int) config('claims.import.chunk_size', 500));
        $start = (($chunkNumber - 1) * $chunkSize) + 2;
        $end = $start + $chunkSize - 1;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv' || $extension === 'txt') {
            $file = new \SplFileObject($path, 'r');
            $file->setFlags(\SplFileObject::READ_CSV);
            $headers = [];
            $rows = [];
            $physicalRow = 0;
            while (! $file->eof()) {
                $row = $file->fgetcsv();
                $physicalRow++;
                if ($physicalRow === 1) {
                    $headers = is_array($row) ? $row : [];

                    continue;
                }
                if ($physicalRow < $start) {
                    continue;
                }
                if ($physicalRow > $end) {
                    break;
                }
                if (is_array($row) && $row !== [null]) {
                    $rows[] = $row;
                }
            }

            return [$headers, $rows, $start];
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new ChunkReadFilter($start, $end));
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $headers = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, false)[0] ?? [];
        $highestDataRow = min($end, (int) $sheet->getHighestDataRow());
        $rows = $start <= $highestDataRow
            ? $sheet->rangeToArray("A{$start}:{$highestColumn}{$highestDataRow}", null, true, false)
            : [];
        $spreadsheet->disconnectWorksheets();

        return [$headers, $rows, $start];
    }

    /** @param array<int, mixed> $headers @return array<string, int> */
    private function mapColumns(array $headers): array
    {
        $lookup = [];
        foreach (self::FIELD_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                $lookup[$this->normalizeHeader($alias)] = $field;
            }
        }

        $map = [];
        foreach ($headers as $index => $header) {
            $field = $lookup[$this->normalizeHeader((string) $header)] ?? null;
            if ($field !== null && ($field === 'claim_status' || ! array_key_exists($field, $map))) {
                $map[$field] = $index;
            }
        }

        return $map;
    }

    /** @param array<int, mixed> $headers */
    private function validateHeaders(array $headers): void
    {
        if (! array_key_exists('bill_id', $this->mapColumns($headers))) {
            throw new RuntimeException('Missing required Tricity column: Bill ID.');
        }
    }

    /** @param array<int, mixed> $row @param array<string, int> $map @return array<string, mixed> */
    private function mapRow(array $row, array $map): array
    {
        $data = [];
        foreach ($map as $field => $index) {
            $data[$field] = $row[$index] ?? null;
        }

        return $data;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizePayload(array $data): array
    {
        $payload = [];
        foreach (self::IMPORTED_FIELDS as $field) {
            $value = $data[$field] ?? null;
            $payload[$field] = in_array($field, self::DECIMAL_FIELDS, true)
                ? $this->decimal($value)
                : (in_array($field, self::DATE_FIELDS, true) ? $this->date($value) : $this->string($value));
        }
        $payload['denial_reason'] = $this->string($data['denial_reason'] ?? null);

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function sourceHash(string $billId, array $payload): string
    {
        return hash('sha256', implode('|', array_map(
            fn ($value): string => strtolower(trim((string) ($value ?? ''))),
            [
                $billId,
                $payload['procedure_code'] ?? $payload['cpt_code'],
                $payload['service_date_start'],
                $payload['activity_type'],
                $payload['primary_modifier'],
            ],
        )));
    }

    /** @param array<string, mixed> $payload */
    private function findExistingClaim(ClaimImport $import, string $billId, string $sourceHash, array $payload): ?Claim
    {
        $claim = Claim::query()
            ->where('account_type', $import->account_type)
            ->where('source_hash', $sourceHash)
            ->first();
        if ($claim) {
            return $claim;
        }

        $query = Claim::query()
            ->where('account_type', $import->account_type)
            ->where('bill_id', $billId)
            ->where(fn ($nested) => $nested->whereNull('last_import_id')->orWhere('last_import_id', '!=', $import->id));

        foreach (['bill_id', 'service_date_start', 'activity_type', 'primary_modifier'] as $field) {
            if (($payload[$field] ?? null) !== null) {
                $query->where($field, $payload[$field]);
            }
        }

        $procedure = $payload['procedure_code'] ?? $payload['cpt_code'] ?? null;
        if ($procedure !== null) {
            $query->where(fn ($nested) => $nested->where('procedure_code', $procedure)->orWhere('cpt_code', $procedure));
        }

        $matched = $query->first();
        if ($matched) {
            return $matched;
        }

        return Claim::query()
            ->where('account_type', $import->account_type)
            ->where('external_id', $billId)
            ->whereNull('procedure_code')
            ->where(fn ($nested) => $nested->whereNull('last_import_id')->orWhere('last_import_id', '!=', $import->id))
            ->first();
    }

    /** @param array<int, mixed> $headers @return array<int, string> */
    private function rawHeaderKeys(array $headers): array
    {
        $used = [];

        return array_map(function ($header) use (&$used): string {
            $base = $this->normalizeHeader((string) $header) ?: 'column';
            $used[$base] = ($used[$base] ?? 0) + 1;

            return $used[$base] === 1 ? $base : "{$base}_{$used[$base]}";
        }, $headers);
    }

    /** @param array<int, mixed> $row @param array<int, string> $keys @return array<string, mixed> */
    private function rawPayload(array $row, array $keys): array
    {
        $payload = [];
        foreach ($keys as $index => $key) {
            $payload[$key] = $row[$index] ?? null;
        }

        return $payload;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)->replace("\xEF\xBB\xBF", '')->trim()->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    private function string(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' || in_array(strtolower($value), ['none', 'n/a', 'na', '-', 'null'], true)
            ? null
            : $value;
    }

    private function decimal(mixed $value): ?float
    {
        if ($this->string($value) === null) {
            return null;
        }

        $number = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $number === '' || $number === '-' ? null : (float) $number;
    }

    private function date(mixed $value): ?string
    {
        if ($this->string($value) === null) {
            return null;
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /** @param array<int|string, mixed> $values */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->string($value) !== null) {
                return false;
            }
        }

        return true;
    }
}
