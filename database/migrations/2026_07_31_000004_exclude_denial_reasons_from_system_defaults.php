<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claim_configuration_system_defaults')) {
            DB::table('claim_configuration_system_defaults')
                ->where('option_type', 'denial_reason')
                ->delete();
        }

        if (Schema::hasColumn('claim_configuration_options', 'system_key')) {
            DB::table('claim_configuration_options')
                ->where('option_type', 'denial_reason')
                ->update(['system_key' => null]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('claim_configuration_system_defaults')
            || ! Schema::hasColumn('claim_configuration_options', 'system_key')) {
            return;
        }

        DB::table('claim_configuration_options')
            ->where('option_type', 'denial_reason')
            ->whereNull('added_by')
            ->orderBy('id')
            ->get()
            ->each(function ($option): void {
                $systemKey = 'dynamic:'.hash('sha256', implode('|', [
                    $option->account_type,
                    $option->option_type,
                    $option->value,
                ]));

                DB::table('claim_configuration_options')
                    ->where('id', $option->id)
                    ->update(['system_key' => $systemKey]);
                DB::table('claim_configuration_system_defaults')->insertOrIgnore([
                    'account_type' => $option->account_type,
                    'option_type' => $option->option_type,
                    'system_key' => $systemKey,
                    'value' => $option->value,
                    'label' => $option->label,
                    'color' => $option->color,
                    'sort_order' => $option->sort_order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
