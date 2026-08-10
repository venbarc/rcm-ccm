<?php

use App\Enums\AccountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $principleTableMap = [
        'claim_configuration_options' => 'principle_claim_configuration_options',
        'claim_configuration_system_defaults' => 'principle_claim_configuration_system_defaults',
        'claim_imports' => 'principle_claim_imports',
        'claims' => 'principle_claims',
        'claim_activities' => 'principle_claim_activities',
        'claim_raw_rows' => 'principle_claim_raw_rows',
        'claim_import_snapshots' => 'principle_claim_import_snapshots',
        'claim_exports' => 'principle_claim_exports',
    ];

    public function up(): void
    {
        foreach ($this->principleTableMap as $sourceTable => $targetTable) {
            if (Schema::hasTable($sourceTable) && ! Schema::hasTable($targetTable)) {
                $this->cloneTable($sourceTable, $targetTable);
            }
        }

        $account = AccountType::Principle->value;

        foreach ([
            'claim_configuration_options',
            'claim_configuration_system_defaults',
            'claim_imports',
            'claims',
            'claim_activities',
            'claim_exports',
        ] as $table) {
            $this->copyRows(
                $table,
                $this->principleTableMap[$table],
                fn (Builder $query): Builder => $query->where('account_type', $account),
            );
        }

        $this->copyRows(
            'claim_raw_rows',
            'principle_claim_raw_rows',
            fn (Builder $query): Builder => $query->whereIn(
                'claim_id',
                DB::table('claims')->select('id')->where('account_type', $account),
            ),
        );
        $this->copyRows(
            'claim_import_snapshots',
            'principle_claim_import_snapshots',
            fn (Builder $query): Builder => $query->whereIn(
                'claim_import_id',
                DB::table('claim_imports')->select('id')->where('account_type', $account),
            ),
        );
    }

    private function cloneTable(string $sourceTable, string $targetTable): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(sprintf('CREATE TABLE `%s` LIKE `%s`', $targetTable, $sourceTable));

            return;
        }

        if ($driver !== 'sqlite') {
            throw new RuntimeException("Cloning account tables is not supported on the [{$driver}] driver.");
        }

        $tableDdl = DB::selectOne(
            'select sql from sqlite_master where type = ? and name = ?',
            ['table', $sourceTable],
        )?->sql;

        if (! is_string($tableDdl) || $tableDdl === '') {
            throw new RuntimeException("Unable to read the schema for [{$sourceTable}].");
        }

        $tableDdl = $this->renameInDdl($tableDdl, 'TABLE', $sourceTable, $targetTable);

        foreach ($this->principleTableMap as $sourceParent => $targetParent) {
            $tableDdl = (string) preg_replace(
                '/(REFERENCES\s+)(["`\[]?)'.preg_quote($sourceParent, '/').'(["`\]]?)/i',
                '$1"'.$targetParent.'"',
                $tableDdl,
            );
        }

        DB::statement($tableDdl);

        $indexes = DB::select(
            'select name, sql from sqlite_master where type = ? and tbl_name = ? and sql is not null',
            ['index', $sourceTable],
        );

        foreach ($indexes as $index) {
            $indexDdl = $this->renameInDdl(
                (string) $index->sql,
                'INDEX',
                (string) $index->name,
                $targetTable.'_'.$index->name,
            );
            $indexDdl = (string) preg_replace(
                '/\bON\s+(["`\[]?)'.preg_quote($sourceTable, '/').'(["`\]]?)/i',
                'ON "'.$targetTable.'"',
                $indexDdl,
                1,
            );

            DB::statement($indexDdl);
        }
    }

    /** @param callable(Builder): Builder $scope */
    private function copyRows(string $sourceTable, string $targetTable, callable $scope): void
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable($targetTable)) {
            return;
        }

        $scope(DB::table($sourceTable))
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($targetTable): void {
                $payload = $rows->map(fn (object $row): array => (array) $row)->all();

                if ($payload !== []) {
                    DB::table($targetTable)->insertOrIgnore($payload);
                }
            });
    }

    private function renameInDdl(string $ddl, string $keyword, string $from, string $to): string
    {
        return (string) preg_replace(
            '/(CREATE\s+(?:UNIQUE\s+)?'.$keyword.'\s+)(["`\[]?)'.preg_quote($from, '/').'(["`\]]?)/i',
            '$1"'.$to.'"',
            $ddl,
            1,
        );
    }

    public function down(): void
    {
        foreach (array_reverse($this->principleTableMap) as $targetTable) {
            Schema::dropIfExists($targetTable);
        }
    }
};
