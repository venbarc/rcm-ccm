<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ClaimImportService
{
    public function __construct(private readonly ClaimActivityService $activities) {}

    public function import(UploadedFile $file, string $account, User $user): ClaimImport
    {
        $storedPath = $file->store('claim-imports');
        $import = ClaimImport::create([
            'account_type' => $account,
            'file_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'imported_by' => $user->id,
        ]);

        try {
            $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
            $headings = array_map(fn (mixed $value): string => $this->normalizeHeading((string) $value), array_shift($rows) ?? []);
            $created = $updated = $skipped = 0;

            DB::transaction(function () use ($rows, $headings, $account, $import, &$created, &$updated, &$skipped): void {
                foreach ($rows as $row) {
                    $values = array_combine(
                        $headings,
                        array_slice(array_pad($row, count($headings), null), 0, count($headings)),
                    );
                    if (! is_array($values) || $this->isEmptyRow($values)) {
                        $skipped++;

                        continue;
                    }

                    $externalId = $this->first($values, ['claim_id', 'claim_number', 'external_id', 'id']);
                    $patientName = $this->first($values, ['patient_name', 'patient', 'name']);
                    if ($externalId === null || $patientName === null) {
                        $skipped++;

                        continue;
                    }

                    $claim = Claim::firstOrNew(['account_type' => $account, 'external_id' => $externalId]);
                    $wasExisting = $claim->exists;
                    $claim->fill([
                        'patient_name' => $patientName,
                        'date_of_service' => $this->date($this->firstRaw($values, ['date_of_service', 'dos', 'service_date'])),
                        'payer' => $this->first($values, ['payer', 'insurance', 'primary_payer']),
                        'provider' => $this->first($values, ['provider', 'rendering_provider']),
                        'cpt_code' => $this->first($values, ['cpt_code', 'cpt', 'procedure_code']),
                        'billed_amount' => $this->money($this->firstRaw($values, ['billed_amount', 'charge_amount', 'charges'])),
                        'balance' => $this->money($this->firstRaw($values, ['balance', 'claim_balance', 'outstanding_balance'])),
                        'status' => $this->status($this->first($values, ['status', 'claim_status'])),
                        'last_import_id' => $import->id,
                    ])->save();

                    $wasExisting ? $updated++ : $created++;
                }
            });

            $import->update([
                'status' => 'completed',
                'created_count' => $created,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'completed_at' => now(),
            ]);
            $this->activities->record($account, 'import', "Imported {$import->file_name}", $user, after: compact('created', 'updated', 'skipped'));
        } catch (\Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 2000),
                'completed_at' => now(),
            ]);
            throw $exception;
        }

        return $import->fresh();
    }

    private function normalizeHeading(string $heading): string
    {
        return Str::of($heading)->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    /** @param array<string, mixed> $values */
    private function isEmptyRow(array $values): bool
    {
        return collect($values)->every(fn (mixed $value): bool => $value === null || trim((string) $value) === '');
    }

    /** @param array<string, mixed> $values @param array<int, string> $keys */
    private function first(array $values, array $keys): ?string
    {
        $value = $this->firstRaw($values, $keys);
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    /** @param array<string, mixed> $values @param array<int, string> $keys */
    private function firstRaw(array $values, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null && trim((string) $values[$key]) !== '') {
                return $values[$key];
            }
        }

        return null;
    }

    private function money(mixed $value): float
    {
        return (float) preg_replace('/[^0-9.-]/', '', (string) ($value ?? 0));
    }

    private function status(?string $value): string
    {
        $status = Str::of($value ?? 'new')->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        return in_array($status, ['new', 'in_progress', 'pending', 'denied', 'appealed', 'paid', 'closed'], true)
            ? $status
            : 'new';
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
