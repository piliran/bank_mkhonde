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
            $table->foreignId('lender_id')->constrained('lenders');
            $table->foreignId('borrower_id')->constrained('borrowers');
            $table->decimal('amount', 15, 2);
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->decimal('repay_amount', 15, 2)->nullable();
            $table->string('status')->default('pending');
            $table->date('borrowed_at')->nullable();
            $table->date('due_at')->nullable();
            $table->date('returned_at')->nullable();
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
