<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRINCIPLE_INVOICE_DATE = '2026-07-31';

    public function up(): void
    {
        if (! Schema::hasTable('principle_claims')) {
            return;
        }

        DB::table('principle_claims')->update([
            'invoiced_status' => 'invoiced',
            'invoiced_status_date' => self::PRINCIPLE_INVOICE_DATE,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('principle_claims')) {
            return;
        }

        DB::table('principle_claims')
            ->where('invoiced_status', 'invoiced')
            ->whereDate('invoiced_status_date', self::PRINCIPLE_INVOICE_DATE)
            ->update([
                'invoiced_status' => null,
                'invoiced_status_date' => null,
            ]);
    }
};
