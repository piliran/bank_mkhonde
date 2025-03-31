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
       
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->nullable();
           
            $table->string('national_id')->nullable();
            $table->string('traditional_authority')->nullable();
            $table->string('home_village')->nullable();
            $table->string('occupation')->nullable();
            $table->string('phone')->nullable();
            $table->string('airtel_money_number')->nullable();
            $table->string('mpamba_number')->nullable();
            $table->string('home_physical_address')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('current_physical_address')->nullable();
            $table->string('guardian')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('company_name')->nullable();
            $table->decimal('lending_limit', 10, 2)->nullable();
            $table->decimal('lending_minimum', 10, 2)->nullable();
            $table->boolean('collateral_required')->default(false);
            $table->string('interest_rate')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('preferred_borrower_criteria')->nullable();
            $table->decimal('company_annual_revenue', 12, 2)->nullable();
            $table->string('business_registration_number')->nullable();
       
            $table->string('expo_push_token')->nullable();

            // Existing fields
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();

            $table->boolean('is_online')->default(false); // Track online status
            $table->timestamp('last_seen')->nullable(); // Store last seen timestamp

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
