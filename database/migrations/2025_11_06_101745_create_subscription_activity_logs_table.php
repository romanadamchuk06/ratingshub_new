<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SUBSCRIPTION ACTIVITY LOGS MIGRATION
     * =====================================
     *
     * Diese Tabelle loggt ALLE Änderungen an Subscriptions:
     *
     * Geloggte Aktionen:
     * - Subscription erstellt (User hat Plan gekauft)
     * - Subscription gekündigt (cancel)
     * - Subscription wieder aktiviert (resume)
     * - Subscription sofort beendet (cancel_now)
     * - Plan gewechselt (Upgrade/Downgrade)
     * - Zahlungsmethode geändert
     * - Zahlung fehlgeschlagen
     * - Zahlung erfolgreich
     * - Promo-Code angewendet
     *
     * Zweck:
     * - Nachvollziehbarkeit: Wer hat welche Subscription-Änderung vorgenommen?
     * - Finance: Zahlungs-Historie
     * - Support: Bei Subscription-Problemen Historie ansehen
     * - Analytics: Churn-Analyse (wer kündigt wann?)
     */
    public function up(): void
    {
        Schema::create('subscription_activity_logs', function (Blueprint $table) {
            $table->id();

            // WER hat die Aktion durchgeführt?
            // NULL = System (z.B. automatische Zahlung)
            $table->foreignId('performed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User/Admin der die Aktion durchgeführt hat (NULL = System)');

            // FÜR WELCHEN User?
            $table->foreignId('target_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User dessen Subscription geändert wurde');

            // WELCHER Plan?
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete()
                ->comment('Betroffener Plan');

            // Plan Name (Snapshot)
            $table->string('plan_name', 255)
                ->nullable()
                ->comment('Name des Plans (Snapshot)');

            // WAS wurde gemacht?
            // subscribed, cancelled, resumed, cancelled_now, plan_changed,
            // payment_method_updated, payment_failed, payment_succeeded, promo_code_applied
            $table->string('action', 50)
                ->comment('Art der Aktion');

            // DETAILS: Was wurde genau geändert?
            // JSON Format: {
            //   "old_plan": "Free",
            //   "new_plan": "Pro",
            //   "promo_code": "SAVE50",
            //   "discount": "50%",
            //   "price": "4.99"
            // }
            $table->json('changes')
                ->nullable()
                ->comment('Details der Änderungen (JSON)');

            // Stripe Subscription ID (falls vorhanden)
            $table->string('stripe_subscription_id', 255)
                ->nullable()
                ->comment('Stripe Subscription ID');

            // IP-Adresse
            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP-Adresse');

            // Optionale Beschreibung
            $table->text('description')
                ->nullable()
                ->comment('Optionale Beschreibung');

            $table->timestamp('created_at')
                ->comment('Zeitpunkt der Aktion');

            // Indices
            $table->index('performed_by_user_id');
            $table->index('target_user_id');
            $table->index('plan_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_activity_logs');
    }
};
