<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_exports', function (Blueprint $table): void {
            $table->id();
            $table->string('account_type')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('status')->default('queued')->index();
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('processed_chunks')->default(0);
            $table->json('filters')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['account_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_exports');
    }
};
