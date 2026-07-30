<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->boolean('credit_status')->nullable()->default(null)->change();
        });

        DB::table('claims')
            ->where('credit_status', false)
            ->update(['credit_status' => null]);
    }

    public function down(): void
    {
        DB::table('claims')
            ->whereNull('credit_status')
            ->update(['credit_status' => false]);

        Schema::table('claims', function (Blueprint $table): void {
            $table->boolean('credit_status')->nullable(false)->default(false)->change();
        });
    }
};
