<?php

use App\Enums\AccountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->string('primary_provider')->nullable()->index()->after('rendering_provider');
            $table->string('modmed_claim_status')->nullable()->index()->after('claim_status');
            $table->date('cf_invoice_date')->nullable()->index()->after('modmed_claim_status');

            $table->index(['account_type', 'bill_id'], 'claims_account_bill_id_index');
        });

        DB::table('claims')
            ->where('account_type', AccountType::Tricity->value)
            ->whereNull('bill_id')
            ->update(['bill_id' => DB::raw('external_id')]);

        DB::table('claims')
            ->where('account_type', AccountType::Tricity->value)
            ->whereNull('primary_provider')
            ->whereNotNull('rendering_provider')
            ->update(['primary_provider' => DB::raw('rendering_provider')]);
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->dropIndex('claims_account_bill_id_index');
            $table->dropColumn([
                'primary_provider',
                'modmed_claim_status',
                'cf_invoice_date',
            ]);
        });
    }
};
