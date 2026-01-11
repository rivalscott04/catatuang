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
        Schema::table('users', function (Blueprint $table) {
            $table->date('subscription_started_at')->nullable()->after('last_reset_at');
            $table->date('subscription_expires_at')->nullable()->after('subscription_started_at');
            $table->enum('subscription_status', ['active', 'expired', 'cancelled'])->default('active')->after('subscription_expires_at');
            
            // Index untuk query expiring soon
            $table->index(['subscription_status', 'subscription_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['subscription_status', 'subscription_expires_at']);
            $table->dropColumn(['subscription_started_at', 'subscription_expires_at', 'subscription_status']);
        });
    }
};
















