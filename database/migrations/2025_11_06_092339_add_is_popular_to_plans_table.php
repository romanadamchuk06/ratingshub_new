<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WARUM diese Migration?
     * ======================
     * Aktuell ist der "Beliebt"-Badge in Pricing.vue hardcoded:
     *   plan.slug === 'pro-single'
     *
     * Problem:
     * - Nicht flexibel: Admin kann nicht ändern welcher Plan "beliebt" ist
     * - Hardcoded slug: Was wenn Plan umbenannt wird?
     *
     * Lösung:
     * - Neue Spalte: is_popular (Boolean)
     * - Admin kann im Plan-Management togglen
     * - Pricing.vue prüft: v-if="plan.is_popular"
     *
     * Vorteile:
     * - Flexibel: Admin entscheidet
     * - Kein Code-Change für Marketing-Entscheidungen
     * - Mehrere Pläne können "beliebt" sein (z.B. Black Friday)
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // is_popular: Zeigt "Beliebt"-Badge auf Pricing-Seite
            $table->boolean('is_popular')
                ->default(false)
                ->after('is_active')
                ->comment('Zeigt "Beliebt"-Badge auf Pricing-Seite');
        });

        // Optional: Aktuellen "Pro"-Plan als beliebt markieren
        // (nur wenn er existiert, z.B. nach Seeders)
        if (Schema::hasTable('plans')) {
            \DB::table('plans')
                ->where('slug', 'pro-single') // Korrigiert: Slug ist "pro-single", nicht "pro"
                ->update(['is_popular' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_popular');
        });
    }
};
