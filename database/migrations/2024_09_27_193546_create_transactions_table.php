<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('borrower_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('transaction_fee', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->enum('type', ['disbursement', 'repayment', 'refund']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled']);
            // $table->enum('payment_method', ['bank_transfer', 'mobile_money', 'cash']);
            $table->string('payment_method', 20);
            $table->string('payment_reference')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['loan_id', 'status']);
            $table->index(['lender_id', 'borrower_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};