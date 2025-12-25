<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin
        // Password: admin123 (change this in production!)
        Admin::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@catatuang.com',
                'password' => Hash::make('admin123'),
                'name' => 'Administrator',
                'is_active' => true,
            ]
        );
    }
}

