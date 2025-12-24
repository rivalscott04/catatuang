<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add columns for monthly counters
            $table->unsignedInteger('chat_count_month')->default(0)->after('is_unlimited');
            $table->unsignedInteger('struk_count_month')->default(0)->after('chat_count_month');
            $table->date('last_reset_at')->nullable()->after('struk_count_month');
        });

        // Update enum plan to include 'vip'
        // Note: MySQL doesn't support ALTER ENUM directly, so we need to modify the column
        DB::statement("ALTER TABLE users MODIFY COLUMN plan ENUM('free', 'pro', 'biz', 'vip') DEFAULT 'free'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['chat_count_month', 'struk_count_month', 'last_reset_at']);
        });

        // Revert enum back to original
        DB::statement("ALTER TABLE users MODIFY COLUMN plan ENUM('free', 'pro', 'biz') DEFAULT 'free'");
    }
};

