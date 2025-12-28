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
        // Change plan from enum to string
        // Use raw SQL to change enum to string
        DB::statement("ALTER TABLE users MODIFY COLUMN plan VARCHAR(255) NOT NULL DEFAULT 'free'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to enum (with the values that were there before)
        DB::statement("ALTER TABLE users MODIFY COLUMN plan ENUM('free', 'pro', 'biz', 'vip') NOT NULL DEFAULT 'free'");
    }
};
