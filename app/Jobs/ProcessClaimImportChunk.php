<?php

namespace App\Jobs;

use App\Models\ClaimImport;
use App\Services\ClaimImportService;
use App\Support\AccountContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessClaimImportChunk implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly int $importId,
        public readonly int $chunkNumber,
        public readonly string $accountType = 'tricity_pain_associates',
    ) {}

    public function handle(ClaimImportService $imports): void
    {
        AccountContext::runWith($this->accountType, function () use ($imports): void {
            $import = ClaimImport::find($this->importId);
            if (! $import || $import->status === 'failed') {
                return;
            }

            $imports->processChunk($import, $this->chunkNumber);
        });
    }

    public function failed(?Throwable $exception): void
    {
        AccountContext::runWith($this->accountType, fn () => app(ClaimImportService::class)->failImport(
            $this->importId,
            $exception?->getMessage() ?? 'The import queue job failed.',
        ));
    }
}
