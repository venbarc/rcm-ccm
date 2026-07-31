<?php

use App\Enums\SystemClaimConfiguration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $accounts = DB::table('claim_configuration_options')
            ->select('account_type')
            ->distinct()
            ->pluck('account_type');

        foreach ($accounts as $account) {
            foreach (SystemClaimConfiguration::forType('credit_status') as $option) {
                DB::table('claim_configuration_options')->insertOrIgnore([
                    'account_type' => $account,
                    'option_type' => 'credit_status',
                    'value' => $option->internalValue(),
                    'label' => $option->label(),
                    'color' => null,
                    'sort_order' => $option->sortOrder(),
                    'added_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('claim_configuration_options')
            ->where('option_type', 'credit_status')
            ->delete();
    }
};
