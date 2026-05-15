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
        Schema::create('accounts', function (Blueprint $table) {

            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('account_type_id')->constrained('account_types');
            $table->foreignId('home_branch_id')->constrained('branches');
            $table->string('account_number')->unique();
            $table->string('status')->default('active');
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
