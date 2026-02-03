<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * PLAN SEEDER (Vereinfacht für Stripe Pricing Table)
     * ===================================================
     *
     * Erstellt 3 Standard-Pläne mit dualen Stripe Price IDs:
     * - Free (kostenlos, keine Stripe ID nötig)
     * - Pro (monatlich + jährlich)
     * - Enterprise (monatlich + jährlich)
     *
     * WICHTIG:
     * - Stripe Price IDs müssen im Admin-Panel konfiguriert werden
     * - Die IDs findest du im Stripe Dashboard unter Produkte → Preise
     * - Format: price_xxxxxxxxxxxxx
     *
     * PREISSTRUKTUR:
     * - Jährlich = 10 Monate (2 Monate gratis / ~17% Rabatt)
     * - max_platforms: 1000 = Unbegrenzt
     */
    public function run(): void
    {
        $plans = [
            // ================== FREE PLAN ==================
            // Kostenloser Einstieg - keine Stripe Integration nötig
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'price' => 0.00,
                'price_yearly' => null,
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

            // ================== PRO PLAN ==================
            // Beliebtester Plan - für kleine/mittlere Unternehmen
            [
                'name' => 'Pro',
                'slug' => 'pro',
                // Stripe Price IDs - im Admin-Panel konfigurieren!
                'stripe_price_id_monthly' => null, // z.B. price_1ABC...
                'stripe_price_id_yearly' => null,  // z.B. price_1XYZ...
                'price' => 29.99,           // €/Monat
                'price_yearly' => 299.99,   // €/Jahr (10 Monate)
                'max_platforms' => 10,
                'is_active' => true,
                'is_popular' => true, // Als "Beliebt" markiert
                'sort_order' => 2,
                'description' => 'Für wachsende Unternehmen mit mehreren Standorten',
                'features' => [
                    '10 verbundene Plattformen',
                    'Bewertungen verwalten & beantworten',
                    'KI-Antwortvorschläge',
                    'Erweiterte Analytics & Reports',
                    'Automatische Sync',
                    'Export-Funktionen',
                    'Priority Support',
                ],
            ],

            // ================== ENTERPRISE PLAN ==================
            // Premium-Plan für große Unternehmen/Agenturen
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                // Stripe Price IDs - im Admin-Panel konfigurieren!
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'price' => 99.99,           // €/Monat
                'price_yearly' => 999.99,   // €/Jahr (10 Monate)
                'max_platforms' => 1000,    // 1000 = Unbegrenzt
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
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

        // Alte Pläne mit monthly/yearly Suffix löschen (Migration von alter Struktur)
        Plan::whereIn('slug', [
            'basic-monthly', 'basic-yearly',
            'pro-monthly', 'pro-yearly',
            'enterprise-monthly', 'enterprise-yearly',
        ])->delete();
    }
}
