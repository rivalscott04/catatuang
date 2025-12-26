<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan unique index untuk mencegah nomor ganda
            // Pastikan data duplikat dibersihkan sebelum menjalankan migration ini.
            if (! $this->hasUniqueIndex($table, 'users_phone_number_unique')) {
                $table->unique('phone_number', 'users_phone_number_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_number_unique');
        });
    }

    /**
     * Helper untuk mengecek apakah index sudah ada.
     */
    private function hasUniqueIndex(Blueprint $table, string $indexName): bool
    {
        $connection = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $connection->listTableIndexes($table->getTable());
        return array_key_exists($indexName, $indexes);
    }
};

