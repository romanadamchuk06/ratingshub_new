<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PLANS: ZWEI STRIPE PRICE IDS PRO PLAN
 * ======================================
 *
 * Vorher: Separate Zeilen für monatlich/jährlich
 * Nachher: Ein Plan mit zwei Stripe Price IDs
 *
 * Beispiel:
 * - Pro Plan:
 *   - stripe_price_id_monthly = price_xxx (14,99€/Monat)
 *   - stripe_price_id_yearly = price_yyy (149,99€/Jahr)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Neue Spalten für monatliche und jährliche Stripe Price IDs
            $table->string('stripe_price_id_monthly')->nullable()->after('slug');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');

            // Monatlicher und jährlicher Preis (für Anzeige)
            $table->decimal('price_yearly', 10, 2)->nullable()->after('price');
        });

        // Daten migrieren: alte stripe_plan_id in neue Spalten übernehmen
        // (manuell in DB oder via Seeder anpassen)
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_price_id_monthly',
                'stripe_price_id_yearly',
                'price_yearly',
            ]);
        });
    }
};
