<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claim_configuration_options', function (Blueprint $table): void {
            $table->string('color', 7)->nullable();
        });

        $defaultColors = [
            'draft' => '#F3F4F6',
            'paid' => '#DCFCE7',
            'rebilled' => '#DBEAFE',
            'appeal' => '#F3E8FF',
            'pending' => '#FEF3C7',
            'void' => '#E2E8F0',
            'corrected' => '#CFFAFE',
            'patient_balance' => '#FCE7F3',
        ];
        $fallbackColors = [
            '#FFEDD5',
            '#CCFBF1',
            '#ECFCCB',
            '#FFE4E6',
            '#E0E7FF',
            '#E0F2FE',
            '#D1FAE5',
            '#EDE9FE',
        ];

        DB::table('claim_configuration_options')
            ->where('option_type', 'work_status')
            ->orderBy('account_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'account_type', 'value'])
            ->groupBy('account_type')
            ->each(function ($options) use ($defaultColors, $fallbackColors): void {
                $usedColors = [];

                foreach ($options as $option) {
                    $color = $defaultColors[$option->value] ?? collect($fallbackColors)
                        ->first(fn (string $candidate): bool => ! in_array($candidate, $usedColors, true));

                    if ($color === null) {
                        throw new RuntimeException("No unique Work Status color is available for account {$option->account_type}.");
                    }

                    DB::table('claim_configuration_options')
                        ->where('id', $option->id)
                        ->update(['color' => $color]);
                    $usedColors[] = $color;
                }
            });

        Schema::table('claim_configuration_options', function (Blueprint $table): void {
            $table->unique(
                ['account_type', 'option_type', 'color'],
                'claim_config_account_type_color_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('claim_configuration_options', function (Blueprint $table): void {
            $table->dropUnique('claim_config_account_type_color_unique');
            $table->dropColumn('color');
        });
    }
};
