<?php

namespace Database\Seeders;

use App\Models\Pricing;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricings = [
            [
                'plan' => 'free',
                'price' => 0,
                'is_active' => true,
                'description' => 'Trial 3 Hari (Gratis)',
            ],
            [
                'plan' => 'pro',
                'price' => 29000,
                'is_active' => true,
                'description' => 'Pro (Rp 29rb/bulan)',
            ],
            [
                'plan' => 'vip',
                'price' => 79000,
                'is_active' => true,
                'description' => 'VIP (Rp 79rb/bulan)',
            ],
        ];

        foreach ($pricings as $pricing) {
            Pricing::updateOrCreate(
                ['plan' => $pricing['plan']],
                $pricing
            );
        }
    }
}


