<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Cek apakah index sudah ada dengan raw SQL
        $indexExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'users' 
            AND index_name = 'users_phone_number_unique'
        ");
        
        if ($indexExists[0]->count == 0) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone_number', 'users_phone_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_number_unique');
        });
    }
};

