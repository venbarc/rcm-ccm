<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->index(['account_type', 'external_id'], 'claims_account_external_index');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->dropIndex('claims_account_external_index');
        });
    }
};
