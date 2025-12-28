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
        Schema::table('pricings', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('is_active');
            $table->boolean('show_on_main')->default(true)->after('display_order');
            $table->string('badge_text')->nullable()->after('show_on_main');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricings', function (Blueprint $table) {
            $table->dropColumn(['display_order', 'show_on_main', 'badge_text']);
        });
    }
};

