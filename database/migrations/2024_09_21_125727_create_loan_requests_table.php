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
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
        $table->foreignId('borrower_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
        $table->decimal('amount', 10, 2);
        $table->decimal('repayment_amount', 10, 2)->nullable();
        $table->integer('repayment_period'); // in months
        $table->decimal('interest_rate', 5, 2);
        $table->foreignId('collateral_id')->nullable()->constrained('collaterals')->onDelete('set null');
        $table->enum('status', ['pending', 'approved', 'rejected', 'withdrawn'])->default('pending');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_requests');
    }
};
