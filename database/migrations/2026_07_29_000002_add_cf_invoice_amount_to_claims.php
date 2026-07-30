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
            $table->decimal('cf_invoice_amount', 15, 2)->nullable()->after('cf_invoice_date');
        });

        DB::table('claim_raw_rows')
            ->select(['id', 'claim_id', 'raw_payload'])
            ->whereNotNull('raw_payload')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $payload = is_string($row->raw_payload)
                        ? json_decode($row->raw_payload, true)
                        : (array) $row->raw_payload;
                    $rawAmount = is_array($payload) ? ($payload['cf_invoice_amount'] ?? null) : null;
                    $amount = preg_replace('/[^0-9.\-]/', '', (string) ($rawAmount ?? ''));

                    if ($amount === '' || $amount === '-' || ! is_numeric($amount)) {
                        continue;
                    }

                    DB::table('claims')
                        ->where('id', $row->claim_id)
                        ->update(['cf_invoice_amount' => (float) $amount]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table): void {
            $table->dropColumn('cf_invoice_amount');
        });
    }
};
