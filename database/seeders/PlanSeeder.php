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
     * - Jährliche Preise = 10 Monate (2 Monate gratis / ~17% Rabatt)
     */
    public function run(): void
    {
        $plans = [
            // ================== FREE PLAN ==================
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_plan_id' => null,
                'price' => 0.00,
                'billing_interval' => 'monthly',
                'max_platforms' => 1,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'description' => 'Perfekt zum Starten und Ausprobieren',
                'features' => [
                    '1 verbundene Plattform',
                    'Bewertungen anzeigen',
                    'Basis-Dashboard',
                    'E-Mail Support',
                ],
            ],

            // ================== BASIC MONATLICH ==================
            [
                'name' => 'Basic',
                'slug' => 'basic-monthly',
                'stripe_plan_id' => null,
                'price' => 9.99,
                'billing_interval' => 'monthly',
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
            // ================== BASIC JÄHRLICH ==================
            [
                'name' => 'Basic',
                'slug' => 'basic-yearly',
                'stripe_plan_id' => null,
                'price' => 99.99, // 10 Monate Preis (2 Monate gratis)
                'billing_interval' => 'yearly',
                'max_platforms' => 3,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
                'description' => 'Ideal für kleine Unternehmen',
                'features' => [
                    '3 verbundene Plattformen',
                    'Bewertungen verwalten',
                    'Auf Bewertungen antworten',
                    'Erweiterte Filter',
                    'E-Mail Support',
                    '2 Monate gratis bei jährlicher Zahlung',
                ],
            ],

            // ================== PRO MONATLICH ==================
            [
                'name' => 'Pro',
                'slug' => 'pro-monthly',
                'stripe_plan_id' => null,
                'price' => 29.99,
                'billing_interval' => 'monthly',
                'max_platforms' => 10,
                'is_active' => true,
                'is_popular' => true, // Pro monatlich als "Beliebt" markiert
                'sort_order' => 4,
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
            // ================== PRO JÄHRLICH ==================
            [
                'name' => 'Pro',
                'slug' => 'pro-yearly',
                'stripe_plan_id' => null,
                'price' => 299.99, // 10 Monate Preis
                'billing_interval' => 'yearly',
                'max_platforms' => 10,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 5,
                'description' => 'Für wachsende Unternehmen mit mehreren Standorten',
                'features' => [
                    '10 verbundene Plattformen',
                    'Alle Basic Features',
                    'Erweiterte Analytics & Reports',
                    'Automatische Sync',
                    'Export-Funktionen',
                    'Priority Support',
                    'Team-Zugriff',
                    '2 Monate gratis bei jährlicher Zahlung',
                ],
            ],

            // ================== ENTERPRISE MONATLICH ==================
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise-monthly',
                'stripe_plan_id' => null,
                'price' => 99.99,
                'billing_interval' => 'monthly',
                'max_platforms' => 1000, // 1000 = Unbegrenzt
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 6,
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
            // ================== ENTERPRISE JÄHRLICH ==================
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise-yearly',
                'stripe_plan_id' => null,
                'price' => 999.99, // 10 Monate Preis
                'billing_interval' => 'yearly',
                'max_platforms' => 1000,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 7,
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
                    '2 Monate gratis bei jährlicher Zahlung',
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
