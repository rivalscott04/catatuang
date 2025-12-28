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
        Schema::table('pricings', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique(['plan']);
        });
        
        // Use raw SQL to change enum to string
        DB::statement("ALTER TABLE pricings MODIFY plan VARCHAR(255) NOT NULL");
        
        // Re-add unique constraint
        Schema::table('pricings', function (Blueprint $table) {
            $table->unique('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricings', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique(['plan']);
        });
        
        // Change back to enum
        DB::statement("ALTER TABLE pricings MODIFY plan ENUM('free', 'pro', 'vip') NOT NULL");
        
        // Re-add unique constraint
        Schema::table('pricings', function (Blueprint $table) {
            $table->unique('plan');
        });
    }
};
