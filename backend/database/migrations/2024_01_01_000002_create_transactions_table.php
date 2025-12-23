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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal');
            $table->bigInteger('amount');
            $table->text('description');
            $table->enum('type', ['income', 'expense']);
            $table->enum('source', ['text', 'receipt'])->default('text');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'tanggal']);
            $table->index(['user_id', 'type', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
