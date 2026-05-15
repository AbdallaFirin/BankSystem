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
        Schema::create('account_types', function (Blueprint $table) {

            $table->id();
            $table->string('type_name');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('min_balance', 15, 2)->default(0);
            $table->boolean('overdraft_allowed')->default(false);
            $table->decimal('withdrawal_limit', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
