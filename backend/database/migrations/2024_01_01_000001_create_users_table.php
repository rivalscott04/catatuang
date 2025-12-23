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
            $table->string('phone_number', 20)->unique();
            $table->string('name', 120)->nullable();
            $table->enum('plan', ['free', 'pro', 'biz'])->default('free');
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->boolean('reminder_enabled')->default(true);
            $table->boolean('is_unlimited')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['reminder_enabled', 'status']);
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
