<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('allocated_by')->constrained('staff')->cascadeOnDelete();   // Vault Cashier
            $table->foreignId('teller_id')->constrained('staff')->cascadeOnDelete();       // Recipient Teller
            $table->foreignId('till_account_id')->constrained('accounts')->cascadeOnDelete(); // TILL-{staff_id}
            $table->decimal('amount', 15, 2);                                              // Opening float given
            $table->string('status')->default('pending');                                  // pending | acknowledged | returned
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_allocations');
    }
};
