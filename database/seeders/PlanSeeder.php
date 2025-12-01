<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * PLAN SEEDER
     * ===========
     *
     * Erstellt Standard-Subscription-Pläne als Fallback
     *
     * WARUM?
     * - Neue Installation braucht sofort nutzbare Pläne
     * - Free-Plan als System-Fallback (für neue User)
     * - Admin kann dann selbst über Toggle entscheiden, welcher Plan "beliebt" ist
     *
     * WICHTIG:
     * - stripe_plan_id muss später manuell im Admin-Panel gesetzt werden
     * - max_platforms: 1000 = Unbegrenzt
     * - is_popular: Standardmäßig FALSE - Admin entscheidet über Toggle im Plan-Management
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_plan_id' => null,
                'price' => 0.00,
                'max_platforms' => 1,
                'is_active' => true,
                'is_popular' => false, // Free ist nicht "beliebt"
                'sort_order' => 1,
                'description' => 'Perfekt zum Starten und Ausprobieren',
                'features' => [
                    '1 verbundene Plattform',
                    'Bewertungen anzeigen',
                    'Basis-Dashboard',
                    'E-Mail Support',
                ],
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'stripe_plan_id' => null, // Wird später im Admin-Panel mit Stripe Price ID gefüllt
                'price' => 9.99,
                'max_platforms' => 3,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
                'description' => 'Ideal für kleine Unternehmen',
                'features' => [
                    '3 verbundene Plattformen',
                    'Bewertungen verwalten',
                    'Auf Bewertungen antworten',
                    'Erweiterte Filter',
                    'E-Mail Support',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'stripe_plan_id' => null,
                'price' => 29.99,
                'max_platforms' => 10,
                'is_active' => true,
                'is_popular' => false, // Admin entscheidet über Toggle im Plan-Management
                'sort_order' => 3,
                'description' => 'Für wachsende Unternehmen mit mehreren Standorten',
                'features' => [
                    '10 verbundene Plattformen',
                    'Alle Basic Features',
                    'Erweiterte Analytics & Reports',
                    'Automatische Sync',
                    'Export-Funktionen',
                    'Priority Support',
                    'Team-Zugriff',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'stripe_plan_id' => null,
                'price' => 99.99,
                'max_platforms' => 1000, // 1000 = Unbegrenzt (gemäß Konvention)
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
                'description' => 'Für große Unternehmen und Agenturen',
                'features' => [
                    'Unbegrenzte Plattformen',
                    'Alle Pro Features',
                    'White Label Lösung',
                    'API Zugriff',
                    'Dedizierter Account Manager',
                    'Custom Integrationen',
                    'SLA Garantie',
                    '24/7 Premium Support',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
