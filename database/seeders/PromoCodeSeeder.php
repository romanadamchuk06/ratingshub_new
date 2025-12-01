<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promoCodes = [
            [
                'code' => 'TEST50',
                'type' => 'percentage',
                'value' => 50.00,
                'max_uses' => 100,
                'description' => '50% Rabatt für Testzwecke',
                'is_active' => true,
            ],
            [
                'code' => 'WELCOME20',
                'type' => 'percentage',
                'value' => 20.00,
                'max_uses' => null, // unlimited
                'description' => '20% Willkommensrabatt',
                'is_active' => true,
            ],
            [
                'code' => 'SAVE10',
                'type' => 'fixed',
                'value' => 10.00,
                'max_uses' => 50,
                'description' => '10€ Rabatt',
                'is_active' => true,
            ],
            [
                'code' => 'EARLYBIRD',
                'type' => 'percentage',
                'value' => 30.00,
                'max_uses' => 20,
                'expires_at' => now()->addDays(30),
                'description' => '30% Early Bird Rabatt (30 Tage gültig)',
                'is_active' => true,
            ],
            [
                'code' => 'FREE100',
                'type' => 'percentage',
                'value' => 100.00,
                'max_uses' => 10,
                'description' => '100% Rabatt für VIP-Tester',
                'is_active' => true,
            ],
        ];

        foreach ($promoCodes as $promoCode) {
            PromoCode::create($promoCode);
        }

        $this->command->info('Promo codes created successfully!');
    }
}
