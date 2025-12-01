<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reactivate all plans first
        DB::table('plans')->update(['is_active' => true]);

        // Update Free plan - 30 days trial, 1 platform
        DB::table('plans')->where('slug', 'free')->update([
            'name' => 'Free',
            'description' => '30 Tage kostenlos testen',
            'price' => 0.00,
            'max_platforms' => 1,
            'analytics' => false,
            'priority_support' => false,
            'api_access' => false,
            'white_label' => false,
            'sort_order' => 1,
            'features' => json_encode([
                '30 Tage kostenlos',
                '1 verbundene Plattform',
                'Basis-Dashboard',
                'Bewertungen anzeigen',
                'E-Mail Support',
            ]),
        ]);

        // Update Basic plan to Pro - 1 platform, paid
        DB::table('plans')->where('slug', 'basic')->update([
            'name' => 'Pro',
            'slug' => 'pro-single',
            'description' => 'Perfekt für Einzelunternehmer',
            'price' => 19.99,
            'max_platforms' => 1,
            'analytics' => true,
            'priority_support' => true,
            'api_access' => false,
            'white_label' => false,
            'sort_order' => 2,
            'is_active' => true,
            'features' => json_encode([
                '1 verbundene Plattform',
                'Alle Bewertungen verwalten',
                'Erweiterte Analytics',
                'Automatische Benachrichtigungen',
                'Priority Support',
            ]),
        ]);

        // Update Pro plan to Unlimited - 3 platforms
        DB::table('plans')->where('slug', 'pro')->update([
            'name' => 'Unlimited',
            'slug' => 'unlimited',
            'description' => 'Für wachsende Unternehmen',
            'price' => 49.99,
            'max_platforms' => 3,
            'analytics' => true,
            'priority_support' => true,
            'api_access' => true,
            'white_label' => true,
            'sort_order' => 3,
            'is_active' => true,
            'features' => json_encode([
                'Bis zu 3 Plattformen',
                'Alle Bewertungen verwalten',
                'Erweiterte Analytics & Insights',
                'Automatische Benachrichtigungen',
                'Priority Support',
                'API-Zugang',
                'White-Label Option',
            ]),
        ]);

        // Deactivate Enterprise plan
        DB::table('plans')->where('slug', 'enterprise')->update([
            'is_active' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original state
        DB::table('plans')->where('slug', 'pro-single')->update([
            'name' => 'Basic',
            'slug' => 'basic',
        ]);

        DB::table('plans')->where('slug', 'unlimited')->update([
            'name' => 'Pro',
            'slug' => 'pro',
        ]);
    }
};
