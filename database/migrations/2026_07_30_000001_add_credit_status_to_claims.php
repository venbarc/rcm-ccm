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
            $table->boolean('credit_status')->nullable()->default(null)->index()->after('invoiced_status_date');
            $table->date('credit_status_date')->nullable()->index()->after('credit_status');
        });

        DB::table('claims')
            ->where('account_type', 'tricity_pain_associates')
            ->whereIn('invoiced_status', ['pending_credit', 'credited'])
            ->update([
                'credit_status' => true,
                'credit_status_date' => DB::raw('invoiced_status_date'),
            ]);

        DB::table('claims')
            ->where('account_type', 'tricity_pain_associates')
            ->update([
                'invoiced_status' => 'invoiced',
                'invoiced_status_date' => DB::raw('cf_invoice_date'),
            ]);
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->dropColumn([
                'credit_status',
                'credit_status_date',
            ]);
        });
    }
};
