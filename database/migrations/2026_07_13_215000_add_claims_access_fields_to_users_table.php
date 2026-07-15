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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('email_verified_at');
            $table->boolean('is_approved')->default(false)->after('is_admin');
            $table->boolean('can_assign_claims')->default(false)->after('is_approved');
            $table->json('account_types')->nullable()->after('can_assign_claims');
        });

        DB::table('users')->update([
            'is_approved' => true,
            'account_types' => json_encode([AccountType::Tricity->value], JSON_THROW_ON_ERROR),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_admin', 'is_approved', 'can_assign_claims', 'account_types']);
        });
    }
};
