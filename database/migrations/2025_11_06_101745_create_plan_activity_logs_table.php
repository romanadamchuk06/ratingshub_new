<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PLAN ACTIVITY LOGS MIGRATION
     * =============================
     *
     * Diese Tabelle loggt ALLE Änderungen an Subscription-Plänen:
     *
     * Geloggte Aktionen:
     * - Plan erstellt
     * - Plan bearbeitet (Preis, Features, Limits)
     * - Plan aktiviert/deaktiviert
     * - Plan als "Beliebt" markiert/entfernt
     * - Plan gelöscht
     * - Sortierung geändert
     * - Stripe Plan ID geändert
     *
     * Zweck:
     * - Nachvollziehbarkeit: Wer hat Preise geändert?
     * - Compliance: Änderungen dokumentieren
     * - Business: Preis-Historie analysieren
     * - Support: Bei Plan-Problemen Historie ansehen
     */
    public function up(): void
    {
        Schema::create('plan_activity_logs', function (Blueprint $table) {
            $table->id();

            // WER hat die Aktion durchgeführt?
            $table->foreignId('performed_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Admin der die Aktion durchgeführt hat');

            // An WELCHEM Plan wurde die Aktion durchgeführt?
            // nullable weil Plan kann gelöscht werden
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete()
                ->comment('Plan an dem die Aktion durchgeführt wurde');

            // Plan Name (gespeichert für den Fall dass Plan gelöscht wird)
            $table->string('plan_name', 255)
                ->comment('Name des Plans (Snapshot)');

            // WAS wurde gemacht?
            // created, updated, deleted, toggled_active, toggled_popular, price_changed
            $table->string('action', 50)
                ->comment('Art der Aktion');

            // DETAILS: Was wurde genau geändert?
            // JSON Format: {
            //   "field": "price",
            //   "old": "9.99",
            //   "new": "14.99"
            // }
            // oder bei Multiple Changes: {
            //   "price": {"old": "9.99", "new": "14.99"},
            //   "max_platforms": {"old": 3, "new": 5}
            // }
            $table->json('changes')
                ->nullable()
                ->comment('Details der Änderungen (JSON)');

            // IP-Adresse
            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP-Adresse des Admins');

            // Optionale Beschreibung
            $table->text('description')
                ->nullable()
                ->comment('Optionale Beschreibung');

            $table->timestamp('created_at')
                ->comment('Zeitpunkt der Aktion');

            // Indices
            $table->index('performed_by_user_id');
            $table->index('plan_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_activity_logs');
    }
};
