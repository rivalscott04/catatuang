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
        Schema::create('blacklisted_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique(); // IPv6 max length is 45
            $table->text('reason')->nullable();
            $table->integer('lockout_count')->default(0); // Jumlah kali lockout sebelum di-blacklist
            $table->integer('total_failed_attempts')->default(0); // Total percobaan gagal
            $table->timestamp('blacklisted_at');
            $table->timestamps();
            
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklisted_ips');
    }
};

