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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Keeping the original 'name' as optional
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('account_type')->nullable(); // New field for 'OtherName'
            $table->string('user_name')->nullable(); // Assuming this maps to 'username' in dataForm
            $table->string('student_id')->nullable(); // New field for 'studentId'
            $table->string('national_id')->nullable(); // New field for 'nationalId'
            $table->string('traditional_authority')->nullable(); // New field for 'traditionalAuthority'
            $table->string('home_village')->nullable(); // New field for 'homeVillage'
            $table->string('occupation')->nullable(); // New field for 'occupation'
            $table->string('phone')->nullable(); // Assuming this maps to 'telPhone'
            $table->string('airtel_money_number')->nullable(); // New field for 'airtelMoneyNumber'
            $table->string('mpamba_number')->nullable(); // New field for 'mpambaNumber'
            $table->string('home_physical_address')->nullable(); // New field for 'homePhysicalAddress'
            $table->string('physical_address')->nullable(); // New field for 'physicalAddress'
            $table->string('current_physical_address')->nullable(); // New field for 'currentPhysicalAddress'
            $table->string('guardian')->nullable(); // New field for 'guardian'
            $table->string('bank_name')->nullable(); // New field for 'bankName'
            $table->string('account_number')->nullable(); // New field for 'accountNumber'
            $table->string('branch')->nullable(); // New field for 'branch'
            $table->decimal('monthly_income', 15, 2)->nullable(); // New field for 'monthlyIncome' with precision
            $table->string('company_name')->nullable(); // New field for 'companyName'
            $table->decimal('lending_limit', 15, 2)->nullable(); // New field for 'lendingLimit' with precision
            $table->boolean('collateral_required')->nullable(); // New field for 'collateralRequired'
            $table->text('preferred_borrower_criteria')->nullable(); // New field for 'preferredBorrowerCriteria'
            $table->decimal('company_annual_revenue', 20, 2)->nullable(); // New field for 'companyAnnualRevenue' with precision
            $table->string('business_registration_number')->nullable(); // New field for 'businessRegistrationNumber'

            // Existing fields
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
