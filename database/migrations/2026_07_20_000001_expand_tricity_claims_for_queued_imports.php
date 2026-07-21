<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claim_imports', function (Blueprint $table): void {
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('processed_chunks')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
        });

        Schema::table('claims', function (Blueprint $table): void {
            $table->decimal('billed_amount', 14, 2)->nullable()->default(null)->change();
            $table->decimal('balance', 14, 2)->nullable()->default(null)->change();
            $table->string('source_hash', 64)->nullable();
            $table->string('uid')->nullable()->index();
            $table->string('bill_id')->nullable()->index();
            $table->string('payer_name')->nullable()->index();
            $table->string('rendering_provider')->nullable()->index();
            $table->decimal('payments', 15, 2)->nullable();
            $table->decimal('new_payments', 15, 2)->nullable();
            $table->decimal('true_balance', 15, 2)->nullable();
            $table->decimal('true_charge', 15, 2)->nullable();
            $table->decimal('adjustments', 15, 2)->nullable();
            $table->string('aging_days')->nullable();
            $table->string('denial_reason')->nullable()->index();
            $table->string('claim_status')->nullable()->index();
            $table->string('work_status')->default('draft')->index();
            $table->boolean('work_status_manually_set')->default(false);
            $table->decimal('claimed_amount', 15, 2)->nullable();
            $table->string('diagnosis_code')->nullable();
            $table->string('first_name')->nullable()->index();
            $table->string('last_name')->nullable()->index();
            $table->string('modifiers')->nullable();
            $table->date('patient_dob')->nullable();
            $table->string('patient_id')->nullable()->index();
            $table->string('payer_category')->nullable();
            $table->string('procedure_code')->nullable()->index();
            $table->string('service_type')->nullable();
            $table->date('service_date_start')->nullable()->index();
            $table->date('service_date_end')->nullable();
            $table->string('subscriber_id')->nullable()->index();
            $table->decimal('units', 10, 2)->nullable();
            $table->string('activity_type')->nullable();
            $table->string('batch_user')->nullable();
            $table->string('batch_name')->nullable();
            $table->string('code_category')->nullable();
            $table->string('coverage_type')->nullable();
            $table->string('division')->nullable();
            $table->string('financial_category')->nullable();
            $table->string('location')->nullable();
            $table->text('source_notes')->nullable();
            $table->string('ordering_provider')->nullable();
            $table->string('package_name')->nullable();
            $table->string('place_of_service_code')->nullable();
            $table->date('posted_date')->nullable();
            $table->string('practice_location')->nullable();
            $table->string('primary_biller')->nullable();
            $table->string('primary_biller_role')->nullable();
            $table->string('primary_modifier')->nullable();
            $table->string('primary_provider_role')->nullable();
            $table->string('quick_code')->nullable();
            $table->string('recorded_by')->nullable();
            $table->string('supervising_provider')->nullable();
            $table->date('transaction_date')->nullable();
        });

        DB::table('claims')->orderBy('id')->chunkById(500, function ($claims): void {
            foreach ($claims as $claim) {
                DB::table('claims')->where('id', $claim->id)->update([
                    'source_hash' => hash('sha256', implode('|', [
                        $claim->account_type,
                        $claim->external_id,
                        $claim->id,
                    ])),
                ]);
            }
        });

        Schema::table('claims', function (Blueprint $table): void {
            $table->dropUnique(['account_type', 'external_id']);
            $table->unique(['account_type', 'source_hash']);
        });

        Schema::create('claim_raw_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->foreignId('claim_import_id')->nullable()->constrained('claim_imports')->nullOnDelete();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique('claim_id');
        });

        Schema::create('claim_import_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_import_id')->constrained('claim_imports')->cascadeOnDelete();
            $table->foreignId('claim_id')->nullable()->constrained('claims')->cascadeOnDelete();
            $table->json('snapshot_data')->nullable();
            $table->timestamps();

            $table->unique(['claim_import_id', 'claim_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_import_snapshots');
        Schema::dropIfExists('claim_raw_rows');

        Schema::table('claims', function (Blueprint $table): void {
            $table->dropUnique(['account_type', 'source_hash']);
            $table->dropColumn([
                'source_hash', 'uid', 'bill_id', 'payer_name', 'rendering_provider',
                'payments', 'new_payments', 'true_balance', 'true_charge', 'adjustments',
                'aging_days', 'denial_reason', 'claim_status', 'work_status',
                'work_status_manually_set', 'claimed_amount', 'diagnosis_code', 'first_name',
                'last_name', 'modifiers', 'patient_dob', 'patient_id', 'payer_category',
                'procedure_code', 'service_type', 'service_date_start', 'service_date_end',
                'subscriber_id', 'units', 'activity_type', 'batch_user', 'batch_name',
                'code_category', 'coverage_type', 'division', 'financial_category', 'location',
                'source_notes', 'ordering_provider', 'package_name', 'place_of_service_code',
                'posted_date', 'practice_location', 'primary_biller', 'primary_biller_role',
                'primary_modifier', 'primary_provider_role', 'quick_code', 'recorded_by',
                'supervising_provider', 'transaction_date',
            ]);
            $table->unique(['account_type', 'external_id']);
        });

        Schema::table('claim_imports', function (Blueprint $table): void {
            $table->dropColumn([
                'total_rows', 'processed_rows', 'total_chunks', 'processed_chunks',
                'failed_count', 'started_at',
            ]);
        });
    }
};
