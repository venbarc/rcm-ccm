<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('principle_claims')) {
            return;
        }

        $existingColumns = Schema::getColumnListing('principle_claims');

        Schema::table('principle_claims', function (Blueprint $table) use ($existingColumns): void {
            $this->addMissingColumns($table, $existingColumns);
        });

        foreach (['primary_claim_id', 'claim_cpt'] as $column) {
            if (in_array($column, $existingColumns, true) && ! Schema::hasIndex('principle_claims', [$column])) {
                Schema::table('principle_claims', fn (Blueprint $table) => $table->index($column));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('principle_claims')) {
            return;
        }

        $columns = array_values(array_intersect($this->sourceColumns(), Schema::getColumnListing('principle_claims')));

        if ($columns !== []) {
            Schema::table('principle_claims', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    /** @param array<int, string> $existingColumns */
    private function addMissingColumns(Blueprint $table, array $existingColumns): void
    {
        if (! in_array('primary_claim_id', $existingColumns, true)) {
            $table->string('primary_claim_id')->nullable()->index();
        }
        if (! in_array('location_name', $existingColumns, true)) {
            $table->string('location_name')->nullable();
        }
        if (! in_array('patient_date_of_birth', $existingColumns, true)) {
            $table->date('patient_date_of_birth')->nullable();
        }
        if (! in_array('chart_number', $existingColumns, true)) {
            $table->string('chart_number')->nullable();
        }
        if (! in_array('responsible_payer', $existingColumns, true)) {
            $table->string('responsible_payer')->nullable();
        }
        if (! in_array('charge_amount', $existingColumns, true)) {
            $table->decimal('charge_amount', 15, 2)->nullable();
        }
        if (! in_array('total_payment', $existingColumns, true)) {
            $table->decimal('total_payment', 15, 2)->nullable();
        }
        if (! in_array('insurance_balance', $existingColumns, true)) {
            $table->decimal('insurance_balance', 15, 2)->nullable();
        }
        if (! in_array('patient_balance', $existingColumns, true)) {
            $table->decimal('patient_balance', 15, 2)->nullable();
        }
        if (! in_array('total_balance', $existingColumns, true)) {
            $table->decimal('total_balance', 15, 2)->nullable();
        }
        if (! in_array('claim_cpt', $existingColumns, true)) {
            $table->string('claim_cpt')->nullable()->index();
        }
        if (! in_array('true_charge_per_unit', $existingColumns, true)) {
            $table->decimal('true_charge_per_unit', 15, 2)->nullable();
        }
    }

    /** @return array<int, string> */
    private function sourceColumns(): array
    {
        return [
            'primary_claim_id',
            'location_name',
            'patient_date_of_birth',
            'chart_number',
            'responsible_payer',
            'charge_amount',
            'total_payment',
            'insurance_balance',
            'patient_balance',
            'total_balance',
            'claim_cpt',
            'true_charge_per_unit',
        ];
    }
};
