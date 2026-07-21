<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_type');
            $table->timestamps();

            $table->unique(['user_id', 'account_type']);
            $table->index(['admin_id', 'account_type']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_approved')->default(true)->after('is_admin');
        });

        Schema::dropIfExists('group_members');
    }
};
