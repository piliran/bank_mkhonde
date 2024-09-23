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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('repayment_period'); // in months
            $table->foreignId('collateral_id')->nullable()->constrained('collaterals')->onDelete('set null');
            $table->enum('status', ['active', 'paid', 'granted', 'repaid'])->default('active');
            $table->timestamp('date_granted')->useCurrent();
            $table->timestamp('repayment_due_date')->nullable();

            $table->decimal('actual_amount_loaned', 10, 2);
            $table->decimal('repayment_amount', 12, 2)->nullable();
            $table->timestamp('date_repaid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
