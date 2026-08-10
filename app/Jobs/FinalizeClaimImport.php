<?php

namespace App\Jobs;

use App\Services\ClaimImportService;
use App\Support\AccountContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class FinalizeClaimImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $importId,
        public readonly string $accountType = 'tricity_pain_associates',
    ) {}

    public function handle(ClaimImportService $imports): void
    {
        AccountContext::runWith(
            $this->accountType,
            fn () => $imports->finalizeImport($this->importId),
        );
    }

    public function failed(?Throwable $exception): void
    {
        AccountContext::runWith($this->accountType, fn () => app(ClaimImportService::class)->failImport(
            $this->importId,
            $exception?->getMessage() ?? 'The import could not be finalized.',
        ));
    }
}
