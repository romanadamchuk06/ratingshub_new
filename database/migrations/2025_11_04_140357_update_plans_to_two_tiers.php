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
        // Deactivate Basic and Enterprise plans
        DB::table('plans')->whereIn('slug', ['basic', 'enterprise'])->update([
            'is_active' => false,
        ]);

        // Update Pro plan with better features
        DB::table('plans')->where('slug', 'pro')->update([
            'description' => 'Perfekt für professionelle Bewertungsverwaltung',
            'max_platforms' => -1, // unlimited
            'analytics' => true,
            'priority_support' => true,
            'api_access' => true,
            'white_label' => true,
            'features' => json_encode([
                'Unbegrenzte Plattformen',
                'Alle Bewertungen verwalten',
                'Erweiterte Analytics & Insights',
                'Automatische Benachrichtigungen',
                'Priority Support',
                'API-Zugang',
                'White-Label Option',
            ]),
        ]);

        // Update Free plan
        DB::table('plans')->where('slug', 'free')->update([
            'description' => 'Kostenlos zum Ausprobieren',
            'features' => json_encode([
                '1 verbundene Plattform',
                'Basis-Dashboard',
                'E-Mail Support',
            ]),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reactivate Basic and Enterprise plans
        DB::table('plans')->whereIn('slug', ['basic', 'enterprise'])->update([
            'is_active' => true,
        ]);
    }
};
