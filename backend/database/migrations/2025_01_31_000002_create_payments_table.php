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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 100)->unique(); // Unique order ID for Pakasir
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('upgrade_token_id')->nullable()->constrained('upgrade_tokens')->onDelete('set null');
            $table->string('plan', 50); // pro, vip, etc
            $table->bigInteger('amount'); // Amount in Rupiah
            $table->bigInteger('fee')->default(0); // Admin fee
            $table->bigInteger('total_payment'); // Total amount + fee
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'expired'])->default('pending');
            $table->string('payment_method', 50)->nullable(); // qris, bank_transfer, etc
            $table->string('pakasir_order_id', 255)->nullable(); // Order ID from Pakasir (might be different from our order_id)
            $table->timestamp('expires_at')->nullable(); // Payment expiry time
            $table->timestamp('completed_at')->nullable(); // When payment was completed
            $table->json('metadata')->nullable(); // Additional data (webhook payload, etc)
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['order_id']);
            $table->index(['upgrade_token_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
