<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seized_collaterals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('collateral_id')->constrained('collaterals')->onDelete('cascade');
            $table->timestamp('seized_at')->useCurrent();
            $table->text('reason')->nullable();
            $table->enum('status', ['held', 'auctioned', 'returned', 'available', 'on hold', 'seized'])->default('seized');
            $table->decimal('recovery_amount', 10, 2)->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();
            $table->unique(['lender_id', 'collateral_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seized_collaterals');
    }
};