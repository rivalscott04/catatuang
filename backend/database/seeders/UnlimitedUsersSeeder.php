<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnlimitedUsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed unlimited users (no package needed)
     */
    public function run(): void
    {
        $unlimitedUsers = [
            [
                'name' => 'Rival',
                'phone_number' => '6287772666911',
            ],
            [
                'name' => 'Syahrina',
                'phone_number' => '6287763025263',
            ],
        ];

        foreach ($unlimitedUsers as $userData) {
            User::updateOrCreate(
                ['phone_number' => $userData['phone_number']],
                [
                    'name' => $userData['name'],
                    'plan' => 'unlimited',
                    'status' => 'active',
                    'reminder_enabled' => true,
                    'response_style' => 'gaul',
                    'chat_count_month' => 0,
                    'struk_count_month' => 0,
                    'last_reset_at' => now()->toDateString(),
                    'subscription_started_at' => now()->toDateString(),
                    'subscription_expires_at' => null, // No expiry for unlimited plan
                    'subscription_status' => 'active',
                ]
            );
        }

        $this->command->info('Unlimited users seeded successfully!');
    }
}

