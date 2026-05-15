<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            // 'opening' | 'midday' | 'closing'
            $table->string('session_type')->default('closing');

            $table->decimal('physical_total', 15, 2)->default(0); // what teller physically counted
            $table->decimal('system_balance', 15, 2)->default(0); // vault ledger balance at time of count
            $table->decimal('difference',     15, 2)->default(0); // physical_total − system_balance

            // 'balanced' | 'overage' | 'shortage'
            $table->string('status')->default('balanced');

            $table->text('notes')->nullable();
            $table->timestamp('counted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
    }
};
