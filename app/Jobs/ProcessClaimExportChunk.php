<?php

namespace App\Jobs;

use App\Services\ClaimExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessClaimExportChunk implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly int $exportId,
        public readonly int $chunkNumber,
    ) {}

    public function handle(ClaimExportService $exports): void
    {
        $exports->processChunk($this->exportId, $this->chunkNumber);
    }

    public function failed(?Throwable $exception): void
    {
        app(ClaimExportService::class)->failExport(
            $this->exportId,
            $exception?->getMessage() ?? 'The export queue job failed.',
        );
    }
}
