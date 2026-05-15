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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('full_name'); 
            
            // Personal Info
            $table->date('dob');
            $table->string('gender');
            $table->string('marital_status')->nullable();
            $table->string('nationality');
            $table->string('religion')->nullable();
            
            // Identity
            $table->string('id_type');
            $table->string('id_number')->unique();
            $table->date('id_issue_date');
            $table->date('id_expiry_date')->nullable();
            
            // Contact
            $table->string('phone')->unique();
            $table->string('secondary_phone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('preferred_contact_method')->default('SMS');
            
            // Address
            $table->string('region_city');
            $table->string('district')->nullable();
            $table->string('street_neighbourhood')->nullable();
            $table->text('address')->nullable();
            
            // Employment
            $table->string('employment_status');
            $table->string('employer_name')->nullable();
            $table->string('monthly_income_range');
            $table->string('source_of_funds');
            $table->string('expected_monthly_transactions')->nullable();
            
            // Next of Kin
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_relation');
            $table->string('next_of_kin_phone');
            
            // Status
            $table->string('kyc_status')->default('pending');
            $table->foreignId('home_branch_id')->constrained('branches');
            $table->foreignId('registered_by')->constrained('staff');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
