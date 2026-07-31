<?php

use App\Enums\SystemClaimConfiguration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claim_configuration_options', function (Blueprint $table): void {
            $table->string('system_key')->nullable()->after('option_type');
            $table->unique(
                ['account_type', 'option_type', 'system_key'],
                'claim_config_account_type_system_key_unique',
            );
        });

        foreach (SystemClaimConfiguration::forType('work_status') as $default) {
            DB::table('claim_configuration_options')
                ->where('option_type', $default->optionType())
                ->whereNull('added_by')
                ->where('value', $default->internalValue())
                ->update(['system_key' => $default->value]);
        }
    }

    public function down(): void
    {
        Schema::table('claim_configuration_options', function (Blueprint $table): void {
            $table->dropUnique('claim_config_account_type_system_key_unique');
            $table->dropColumn('system_key');
        });
    }
};
