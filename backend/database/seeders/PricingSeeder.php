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
                    'Semua fitur Pro aktif',
                ],
            ],
            [
                'plan' => 'pro',
                'price' => 29000,
                'is_active' => true,
                'description' => 'Fitur lebih lengkap untuk analisis mendalam.',
                'features' => [
                    '200 chat text /bulan',
                    'Upload struk otomatis (50/bulan)',
                    'OCR struk otomatis',
                    'Ringkasan & laporan bulanan',
                    'Export PDF & Excel',
                ],
            ],
            [
                'plan' => 'vip',
                'price' => 79000,
                'is_active' => true,
                'description' => 'Untuk power user atau bisnis.',
                'features' => [
                    'Semua fitur Pro',
                    'Unlimited chat text',
                    'Upload struk 200/bulan',
                    'Priority OCR processing',
                    'Priority Support',
                ],
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


