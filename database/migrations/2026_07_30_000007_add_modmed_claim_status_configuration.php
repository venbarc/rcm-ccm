<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AUTOMATIC_COLORS = [
        '#F8FAFC',
        '#F3E8FF',
        '#DBEAFE',
        '#FEE2E2',
        '#F3F4F6',
        '#FEF3C7',
        '#E0F2FE',
        '#CCFBF1',
        '#DCFCE7',
        '#FFEDD5',
        '#FCE7F3',
        '#E0E7FF',
        '#ECFCCB',
        '#FFE4E6',
        '#CFFAFE',
        '#D1FAE5',
    ];

    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->boolean('modmed_claim_status_manually_set')
                ->default(false)
                ->after('modmed_claim_status');
        });

        $now = now();
        DB::table('claims')
            ->select(['account_type', 'modmed_claim_status'])
            ->whereNotNull('modmed_claim_status')
            ->whereRaw("TRIM(modmed_claim_status) != ''")
            ->distinct()
            ->orderBy('account_type')
            ->orderBy('modmed_claim_status')
            ->get()
            ->groupBy('account_type')
            ->each(function ($statuses, string $account) use ($now): void {
                $usedColors = DB::table('claim_configuration_options')
                    ->where('account_type', $account)
                    ->where('option_type', 'modmed_claim_status')
                    ->whereNotNull('color')
                    ->pluck('color')
                    ->map(fn (string $color): string => strtoupper($color))
                    ->all();
                $maxSortOrder = DB::table('claim_configuration_options')
                    ->where('account_type', $account)
                    ->where('option_type', 'modmed_claim_status')
                    ->max('sort_order');
                $sortOrder = $maxSortOrder === null ? 0 : ((int) $maxSortOrder) + 1;

                foreach ($statuses->values() as $statusRow) {
                    $status = trim((string) $statusRow->modmed_claim_status);
                    $exists = DB::table('claim_configuration_options')
                        ->where('account_type', $account)
                        ->where('option_type', 'modmed_claim_status')
                        ->where('value', $status)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $color = $this->nextColor($usedColors, "{$account}:{$status}");

                    DB::table('claim_configuration_options')->insert([
                        'account_type' => $account,
                        'option_type' => 'modmed_claim_status',
                        'value' => $status,
                        'label' => $status,
                        'color' => $color,
                        'sort_order' => $sortOrder,
                        'added_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $usedColors[] = $color;
                    $sortOrder++;
                }
            });
    }

    public function down(): void
    {
        DB::table('claim_configuration_options')
            ->where('option_type', 'modmed_claim_status')
            ->delete();

        Schema::table('claims', function (Blueprint $table): void {
            $table->dropColumn('modmed_claim_status_manually_set');
        });
    }

    /** @param array<int, string> $usedColors */
    private function nextColor(array $usedColors, string $seed): string
    {
        foreach (self::AUTOMATIC_COLORS as $color) {
            if (! in_array($color, $usedColors, true)) {
                return $color;
            }
        }

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $hash = hexdec(substr(hash('sha256', "{$seed}:{$attempt}"), 0, 6));
            $color = sprintf(
                '#%02X%02X%02X',
                220 + (($hash >> 16) & 31),
                220 + (($hash >> 8) & 31),
                220 + ($hash & 31),
            );

            if (! in_array($color, $usedColors, true)) {
                return $color;
            }
        }

        throw new RuntimeException("Unable to allocate a unique ModMed Claim Status color for {$seed}.");
    }
};
