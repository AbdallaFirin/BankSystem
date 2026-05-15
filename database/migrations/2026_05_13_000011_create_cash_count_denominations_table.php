<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_count_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_count_id')
                  ->constrained('cash_counts')
                  ->cascadeOnDelete();

            $table->decimal('denomination', 10, 2); // e.g. 100.00 / 50.00 / 0.25
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 15, 2);      // denomination × quantity
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_count_denominations');
    }
};
