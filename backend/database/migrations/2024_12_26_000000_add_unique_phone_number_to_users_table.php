<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cek apakah index sudah ada sebelum membuat
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $doctrineSchemaManager->listTableIndexes('users');
            
            if (!isset($indexes['users_phone_number_unique'])) {
                $table->unique('phone_number', 'users_phone_number_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_number_unique');
        });
    }
};

