<?php

namespace App\Jobs;

use App\Services\ClaimImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class FinalizeClaimImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $importId) {}

    public function handle(ClaimImportService $imports): void
    {
        $imports->finalizeImport($this->importId);
    }

    public function failed(?Throwable $exception): void
    {
        app(ClaimImportService::class)->failImport(
            $this->importId,
            $exception?->getMessage() ?? 'The import could not be finalized.',
        );
    }
}
