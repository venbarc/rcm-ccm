<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->string('invoiced_status')->nullable()->index()->after('cf_invoice_amount');
            $table->date('invoiced_status_date')->nullable()->index()->after('invoiced_status');
            $table->string('credit_reason')->nullable()->after('invoiced_status_date');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->dropColumn([
                'invoiced_status',
                'invoiced_status_date',
                'credit_reason',
            ]);
        });
    }
};
