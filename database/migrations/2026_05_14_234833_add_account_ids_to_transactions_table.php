<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Primary account (customer account for deposit/withdrawal; sender for transfer)
            $table->unsignedBigInteger('account_id')->nullable()->after('amount');
            // Secondary account (vault/till for deposit/withdrawal; recipient for transfer)
            $table->unsignedBigInteger('to_account_id')->nullable()->after('account_id');
            $table->string('rejection_reason')->nullable()->after('to_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['account_id', 'to_account_id', 'rejection_reason']);
        });
    }
};
