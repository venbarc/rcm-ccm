<?php

namespace App\Jobs;

use App\Models\ClaimImport;
use App\Services\ClaimImportService;
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
    ) {}

    public function handle(ClaimImportService $imports): void
    {
        $import = ClaimImport::find($this->importId);
        if (! $import || $import->status === 'failed') {
            return;
        }

        $imports->processChunk($import, $this->chunkNumber);
    }

    public function failed(?Throwable $exception): void
    {
        app(ClaimImportService::class)->failImport(
            $this->importId,
            $exception?->getMessage() ?? 'The import queue job failed.',
        );
    }
}
