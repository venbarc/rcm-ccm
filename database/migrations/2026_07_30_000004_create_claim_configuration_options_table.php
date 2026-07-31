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
        Schema::create('claim_configuration_options', function (Blueprint $table): void {
            $table->id();
            $table->string('account_type');
            $table->string('option_type');
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['account_type', 'option_type', 'value'], 'claim_config_account_type_value_unique');
            $table->index(['account_type', 'option_type', 'sort_order'], 'claim_config_account_type_sort_index');
        });

        $now = now();
        $accounts = [
            'tricity_pain_associates',
            'principle_spine_and_pain',
            'wc_health',
        ];
        $defaults = [
            'work_status' => collect(SystemClaimConfiguration::forType('work_status'))
                ->mapWithKeys(fn (SystemClaimConfiguration $option): array => [$option->internalValue() => $option->label()])
                ->all(),
            'credit_reason' => collect(SystemClaimConfiguration::forType('credit_reason'))
                ->mapWithKeys(fn (SystemClaimConfiguration $option): array => [$option->internalValue() => $option->label()])
                ->all(),
        ];
        $rows = [];

        foreach ($accounts as $account) {
            foreach ($defaults as $type => $options) {
                $sortOrder = 0;
                foreach ($options as $value => $label) {
                    $rows[] = [
                        'account_type' => $account,
                        'option_type' => $type,
                        'value' => $value,
                        'label' => $label,
                        'sort_order' => $sortOrder,
                        'added_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $sortOrder++;
                }
            }
        }

        DB::table('claim_configuration_options')->insert($rows);

        if (Schema::hasTable('claims')) {
            DB::table('claims')
                ->select(['account_type', 'denial_reason'])
                ->whereNotNull('denial_reason')
                ->where('denial_reason', '!=', '')
                ->distinct()
                ->orderBy('account_type')
                ->orderBy('denial_reason')
                ->get()
                ->groupBy('account_type')
                ->each(function ($reasons, string $account) use ($now): void {
                    foreach ($reasons->values() as $sortOrder => $reason) {
                        DB::table('claim_configuration_options')->insertOrIgnore([
                            'account_type' => $account,
                            'option_type' => 'denial_reason',
                            'value' => (string) $reason->denial_reason,
                            'label' => (string) $reason->denial_reason,
                            'sort_order' => $sortOrder,
                            'added_by' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_configuration_options');
    }
};
