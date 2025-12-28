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
                'description' => 'Coba semua fitur utama sebelum berlangganan.',
                'features' => [
                    'Aktif 3 hari',
                    '10 chat text /bulan',
                    'Upload struk (1/bulan)',
                ],
                'display_order' => 1,
                'show_on_main' => true,
                'badge_text' => null,
            ],
            [
                'plan' => 'starter',
                'price' => 25000,
                'is_active' => true,
                'description' => 'Paket untuk memulai catat keuangan.',
                'features' => [
                    '20 chat text /bulan',
                    'Upload struk (5/bulan)',
                    'Subscription 30 hari',
                ],
                'display_order' => 2,
                'show_on_main' => true,
                'badge_text' => 'Layak Dicoba',
            ],
            [
                'plan' => 'pro',
                'price' => 45000,
                'is_active' => true,
                'description' => 'Fitur lebih lengkap untuk analisis mendalam.',
                'features' => [
                    '50 chat text /bulan',
                    'Upload struk (10/bulan)',
                    'OCR struk otomatis',
                    'Ringkasan & laporan bulanan',
                    'Subscription 30 hari',
                ],
                'display_order' => 3,
                'show_on_main' => true,
                'badge_text' => 'Best Seller',
            ],
            [
                'plan' => 'vip',
                'price' => 100000,
                'is_active' => true,
                'description' => 'Untuk power user atau bisnis.',
                'features' => [
                    '100 chat text /bulan',
                    'Upload struk (20/bulan)',
                    'Export CSV & PDF',
                    'Priority OCR processing',
                    'Priority Support',
                    'Subscription 30 hari',
                ],
                'display_order' => 4,
                'show_on_main' => true,
                'badge_text' => null,
            ],
            [
                'plan' => 'unlimited',
                'price' => 0,
                'is_active' => true,
                'description' => 'Paket unlimited untuk semua fitur tanpa batas.',
                'features' => [
                    'Unlimited chat text',
                    'Unlimited upload struk',
                    'Export CSV & PDF',
                    'Priority OCR processing',
                    'Priority Support',
                    'Tanpa batas waktu',
                ],
                'display_order' => 5,
                'show_on_main' => false,
                'badge_text' => null,
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


