<?php

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Models\Claim;
use App\Models\ClaimActivity;
use App\Models\ClaimExport;
use App\Models\ClaimImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResetClaimsImport extends Command
{
    protected $signature = 'reset-claims:import
        {--tricity : Reset Tricity Pain Associates claim data}
        {--force : Skip the destructive-action confirmation}';

    protected $description = 'Remove claim data and generated claim files for a selected account before a fresh import';

    public function handle(): int
    {
        if (! $this->option('tricity')) {
            $this->error('Select an account to reset. Currently supported: --tricity');

            return self::INVALID;
        }

        $account = AccountType::Tricity->value;
        $accountLabel = AccountType::Tricity->label();
        $counts = [
            'claims' => Claim::query()->where('account_type', $account)->count(),
            'activities' => ClaimActivity::query()->where('account_type', $account)->count(),
            'imports' => ClaimImport::query()->where('account_type', $account)->count(),
            'exports' => ClaimExport::query()->where('account_type', $account)->count(),
        ];
        $activeImports = ClaimImport::query()
            ->where('account_type', $account)
            ->whereIn('status', ['queued', 'processing'])
            ->count();
        $activeExports = ClaimExport::query()
            ->where('account_type', $account)
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        $this->table(
            ['Account', 'Claims', 'Activities', 'Imports', 'Exports'],
            [[
                $accountLabel,
                number_format($counts['claims']),
                number_format($counts['activities']),
                number_format($counts['imports']),
                number_format($counts['exports']),
            ]],
        );

        if ($activeImports > 0 || $activeExports > 0) {
            $this->warn(
                "This reset will cancel {$activeImports} active import(s) and {$activeExports} active export(s). ".
                'Their queued jobs will safely exit after their records are removed.',
            );
        }

        if (! $this->option('force') && ! $this->confirm(
            "Permanently remove all {$accountLabel} claims, claim activity, import/export history, and stored claim files?",
            false,
        )) {
            $this->components->info('Reset cancelled. No data was changed.');

            return self::SUCCESS;
        }

        $importPaths = ClaimImport::query()
            ->where('account_type', $account)
            ->whereNotNull('stored_path')
            ->pluck('stored_path')
            ->filter()
            ->unique()
            ->values();
        $exportPaths = ClaimExport::query()
            ->where('account_type', $account)
            ->pluck('file_path')
            ->filter()
            ->unique()
            ->values();

        $deleted = DB::transaction(function () use ($account): array {
            $activities = ClaimActivity::query()->where('account_type', $account)->delete();
            $claims = Claim::query()->where('account_type', $account)->delete();
            $imports = ClaimImport::query()->where('account_type', $account)->delete();
            $exports = ClaimExport::query()->where('account_type', $account)->delete();

            return compact('claims', 'activities', 'imports', 'exports');
        });

        $failedFiles = [];
        $deletedFiles = 0;

        foreach ($importPaths->merge($exportPaths)->unique() as $path) {
            if (! Storage::exists($path)) {
                continue;
            }

            if (Storage::delete($path)) {
                $deletedFiles++;
            } else {
                $failedFiles[] = $path;
            }
        }

        $exportDirectory = 'claim-exports/'.Str::slug($account);
        if (Storage::directoryExists($exportDirectory) && ! Storage::deleteDirectory($exportDirectory)) {
            $failedFiles[] = $exportDirectory;
        }

        $this->components->info("{$accountLabel} claim data was reset successfully.");
        $this->table(
            ['Claims', 'Activities', 'Imports', 'Exports', 'Stored files'],
            [[
                number_format($deleted['claims']),
                number_format($deleted['activities']),
                number_format($deleted['imports']),
                number_format($deleted['exports']),
                number_format($deletedFiles),
            ]],
        );
        $this->line('Users, teams, permissions, and other account data were preserved.');

        if ($failedFiles !== []) {
            $this->warn('Database data was removed, but these stored paths could not be deleted:');
            foreach ($failedFiles as $path) {
                $this->line(" - {$path}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
