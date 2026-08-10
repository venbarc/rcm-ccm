<?php

namespace App\Jobs;

use App\Services\ClaimExportService;
use App\Support\AccountContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class FinalizeClaimExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $exportId,
        public readonly string $accountType = 'tricity_pain_associates',
    ) {}

    public function handle(ClaimExportService $exports): void
    {
        AccountContext::runWith(
            $this->accountType,
            fn () => $exports->finalizeExport($this->exportId),
        );
    }

    public function failed(?Throwable $exception): void
    {
        AccountContext::runWith($this->accountType, fn () => app(ClaimExportService::class)->failExport(
            $this->exportId,
            $exception?->getMessage() ?? 'The export could not be finalized.',
        ));
    }
}
