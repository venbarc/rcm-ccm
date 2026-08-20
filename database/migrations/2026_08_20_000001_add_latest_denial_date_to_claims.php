<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = ['claims', 'principle_claims'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'latest_denial_date')) {
                continue;
            }

            $hasDenialReasonId = Schema::hasColumn($table, 'denial_reason_id');

            // Existing rows stay empty on purpose: nothing recorded when those denials
            // landed, so a date only appears once the denial reason is changed.
            Schema::table($table, function (Blueprint $blueprint) use ($hasDenialReasonId): void {
                $column = $blueprint->date('latest_denial_date')->nullable();

                if ($hasDenialReasonId) {
                    $column->after('denial_reason_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'latest_denial_date')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('latest_denial_date'));
            }
        }
    }
};
