<?php

use App\Enums\AccountType;
use App\Enums\SystemClaimConfiguration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_configuration_system_defaults', function (Blueprint $table): void {
            $table->id();
            $table->string('account_type');
            $table->string('option_type');
            $table->string('system_key');
            $table->string('value');
            $table->string('label');
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['account_type', 'option_type', 'system_key'],
                'claim_config_defaults_account_type_key_unique',
            );
            $table->index(
                ['account_type', 'option_type', 'sort_order'],
                'claim_config_defaults_account_type_sort_index',
            );
        });

        $accounts = collect(AccountType::cases())
            ->map(fn (AccountType $account): string => $account->value)
            ->merge(DB::table('claim_configuration_options')->distinct()->pluck('account_type'))
            ->unique()
            ->values();

        foreach ($accounts as $account) {
            foreach (SystemClaimConfiguration::cases() as $default) {
                DB::table('claim_configuration_options')->insertOrIgnore([
                    'account_type' => $account,
                    'option_type' => $default->optionType(),
                    'system_key' => $default->value,
                    'value' => $default->internalValue(),
                    'label' => $default->label(),
                    'color' => $default->color(),
                    'sort_order' => $default->sortOrder(),
                    'added_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('claim_configuration_options')
                    ->where('account_type', $account)
                    ->where('option_type', $default->optionType())
                    ->whereNull('added_by')
                    ->where('value', $default->internalValue())
                    ->update(['system_key' => $default->value]);

                DB::table('claim_configuration_system_defaults')->insertOrIgnore([
                    'account_type' => $account,
                    'option_type' => $default->optionType(),
                    'system_key' => $default->value,
                    'value' => $default->internalValue(),
                    'label' => $default->label(),
                    'color' => $default->color(),
                    'sort_order' => $default->sortOrder(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('claim_configuration_options')
            ->whereNull('added_by')
            ->where('option_type', '!=', 'denial_reason')
            ->orderBy('id')
            ->get()
            ->each(function ($option): void {
                $builtIn = SystemClaimConfiguration::find($option->option_type, $option->value);
                $systemKey = $builtIn?->value
                    ?? (filled($option->system_key) && str_starts_with((string) $option->system_key, 'dynamic:')
                        ? (string) $option->system_key
                        : 'dynamic:'.hash('sha256', implode('|', [
                            $option->account_type,
                            $option->option_type,
                            $option->value,
                        ])));

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

    public function down(): void
    {
        Schema::dropIfExists('claim_configuration_system_defaults');
    }
};
