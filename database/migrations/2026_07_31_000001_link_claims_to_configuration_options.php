<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, int> */
    private array $resolvedOptionIds = [];

    private const AUTOMATIC_COLORS = [
        '#F8FAFC', '#F3E8FF', '#DBEAFE', '#FEE2E2', '#F3F4F6', '#FEF3C7',
        '#E0F2FE', '#CCFBF1', '#DCFCE7', '#FFEDD5', '#FCE7F3', '#E0E7FF',
        '#ECFCCB', '#FFE4E6', '#CFFAFE', '#D1FAE5',
    ];

    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->foreignId('work_status_id')
                ->nullable()
                ->after('work_status')
                ->constrained('claim_configuration_options')
                ->restrictOnDelete();
            $table->foreignId('modmed_claim_status_id')
                ->nullable()
                ->after('modmed_claim_status')
                ->constrained('claim_configuration_options')
                ->restrictOnDelete();
            $table->foreignId('credit_status_id')
                ->nullable()
                ->after('credit_status')
                ->constrained('claim_configuration_options')
                ->restrictOnDelete();
            $table->foreignId('credit_reason_id')
                ->nullable()
                ->after('credit_reason')
                ->constrained('claim_configuration_options')
                ->restrictOnDelete();
            $table->foreignId('denial_reason_id')
                ->nullable()
                ->after('denial_reason')
                ->constrained('claim_configuration_options')
                ->restrictOnDelete();

            $table->index(['account_type', 'work_status_id'], 'claims_account_work_status_id_index');
            $table->index(['account_type', 'modmed_claim_status_id'], 'claims_account_modmed_status_id_index');
            $table->index(['account_type', 'credit_status_id'], 'claims_account_credit_status_id_index');
            $table->index(['account_type', 'credit_reason_id'], 'claims_account_credit_reason_id_index');
            $table->index(['account_type', 'denial_reason_id'], 'claims_account_denial_reason_id_index');
        });

        DB::table('claims')
            ->select([
                'id', 'account_type', 'work_status', 'modmed_claim_status',
                'credit_status', 'credit_reason', 'denial_reason',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($claims): void {
                foreach ($claims as $claim) {
                    $account = (string) $claim->account_type;
                    $workStatus = trim((string) $claim->work_status) ?: 'draft';
                    $modMedStatus = $this->nullableValue($claim->modmed_claim_status);
                    $creditReason = $this->nullableValue($claim->credit_reason);
                    $denialReason = $this->nullableValue($claim->denial_reason);
                    $creditStatus = $claim->credit_status === null
                        ? null
                        : ((bool) $claim->credit_status ? 'yes' : 'no');

                    DB::table('claims')->where('id', $claim->id)->update([
                        'work_status_id' => $this->optionId($account, 'work_status', $workStatus),
                        'modmed_claim_status_id' => $this->optionId($account, 'modmed_claim_status', $modMedStatus),
                        'credit_status_id' => $this->optionId($account, 'credit_status', $creditStatus),
                        'credit_reason_id' => $this->optionId($account, 'credit_reason', $creditReason),
                        'denial_reason_id' => $this->optionId($account, 'denial_reason', $denialReason),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->dropIndex('claims_account_work_status_id_index');
            $table->dropIndex('claims_account_modmed_status_id_index');
            $table->dropIndex('claims_account_credit_status_id_index');
            $table->dropIndex('claims_account_credit_reason_id_index');
            $table->dropIndex('claims_account_denial_reason_id_index');
            $table->dropConstrainedForeignId('work_status_id');
            $table->dropConstrainedForeignId('modmed_claim_status_id');
            $table->dropConstrainedForeignId('credit_status_id');
            $table->dropConstrainedForeignId('credit_reason_id');
            $table->dropConstrainedForeignId('denial_reason_id');
        });
    }

    private function nullableValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function optionId(string $account, string $type, ?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $cacheKey = $account.':'.$type.':'.mb_strtolower($value);
        if (isset($this->resolvedOptionIds[$cacheKey])) {
            return $this->resolvedOptionIds[$cacheKey];
        }

        $option = DB::table('claim_configuration_options')
            ->where('account_type', $account)
            ->where('option_type', $type)
            ->where(function ($query) use ($value): void {
                $query->where('value', $value)
                    ->orWhereRaw('LOWER(label) = ?', [mb_strtolower($value)]);
            })
            ->first(['id']);

        if ($option) {
            return $this->resolvedOptionIds[$cacheKey] = (int) $option->id;
        }

        $label = in_array($type, ['work_status', 'credit_status'], true)
            ? Str::of($value)->replace('_', ' ')->title()->toString()
            : $value;
        $id = DB::table('claim_configuration_options')->insertGetId([
            'account_type' => $account,
            'option_type' => $type,
            'value' => $value,
            'label' => $label,
            'color' => in_array($type, ['work_status', 'modmed_claim_status'], true)
                ? $this->nextColor($account, $type, $value)
                : null,
            'sort_order' => ((int) DB::table('claim_configuration_options')
                ->where('account_type', $account)
                ->where('option_type', $type)
                ->max('sort_order')) + 1,
            'added_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->resolvedOptionIds[$cacheKey] = (int) $id;
    }

    private function nextColor(string $account, string $type, string $seed): string
    {
        $usedColors = DB::table('claim_configuration_options')
            ->where('account_type', $account)
            ->where('option_type', $type)
            ->whereNotNull('color')
            ->pluck('color')
            ->map(fn (string $color): string => strtoupper($color))
            ->all();

        foreach (self::AUTOMATIC_COLORS as $color) {
            if (! in_array($color, $usedColors, true)) {
                return $color;
            }
        }

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $hash = hexdec(substr(hash('sha256', "{$account}:{$type}:{$seed}:{$attempt}"), 0, 6));
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

        throw new RuntimeException("Unable to allocate a unique {$type} color for {$account}.");
    }
};
