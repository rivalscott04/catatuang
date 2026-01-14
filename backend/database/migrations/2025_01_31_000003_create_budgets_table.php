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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('month')->comment('Month (1-12)');
            $table->integer('year')->comment('Year (e.g., 2025)');
            $table->bigInteger('budget_amount')->default(0)->comment('Budget amount in rupiah');
            $table->timestamps();

            // Unique constraint: one budget per user per month per year
            $table->unique(['user_id', 'month', 'year'], 'budget_user_month_year_unique');
            
            // Index for faster queries
            $table->index(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
