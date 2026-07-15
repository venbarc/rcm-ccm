<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('account_type')->index();
            $table->string('file_name');
            $table->string('stored_path')->nullable();
            $table->string('status')->default('processing')->index();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('imported_by')->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->string('account_type')->index();
            $table->string('external_id');
            $table->string('patient_name');
            $table->date('date_of_service')->nullable()->index();
            $table->string('payer')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->string('cpt_code')->nullable()->index();
            $table->decimal('billed_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('status')->default('new')->index();
            $table->string('priority')->default('normal')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->text('notes')->nullable();
            $table->foreignId('last_import_id')->nullable()->constrained('claim_imports')->nullOnDelete();
            $table->timestamps();

            $table->unique(['account_type', 'external_id']);
        });

        Schema::create('claim_activities', function (Blueprint $table): void {
            $table->id();
            $table->string('account_type')->index();
            $table->foreignId('claim_id')->nullable()->constrained('claims')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('description');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_activities');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('claim_imports');
    }
};
