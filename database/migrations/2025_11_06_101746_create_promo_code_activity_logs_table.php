<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PROMO CODE ACTIVITY LOGS MIGRATION
     * ===================================
     *
     * Diese Tabelle loggt ALLE Änderungen an Promo-Codes:
     *
     * Geloggte Aktionen:
     * - Promo-Code erstellt
     * - Promo-Code bearbeitet
     * - Promo-Code aktiviert/deaktiviert
     * - Promo-Code gelöscht
     * - Promo-Code verwendet (von User)
     * - Promo-Code abgelaufen
     *
     * Zweck:
     * - Nachvollziehbarkeit: Wer hat welchen Code erstellt/geändert?
     * - Finance: Rabatt-Tracking
     * - Marketing: Erfolg von Kampagnen messen
     * - Fraud Detection: Missbrauch erkennen
     */
    public function up(): void
    {
        Schema::create('promo_code_activity_logs', function (Blueprint $table) {
            $table->id();

            // WER hat die Aktion durchgeführt?
            // NULL = System (z.B. automatisches Ablaufen)
            $table->foreignId('performed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User/Admin der die Aktion durchgeführt hat (NULL = System)');

            // WELCHER Promo-Code?
            $table->foreignId('promo_code_id')
                ->nullable()
                ->constrained('promo_codes')
                ->nullOnDelete()
                ->comment('Betroffener Promo-Code');

            // Code (Snapshot für den Fall dass gelöscht)
            $table->string('promo_code', 50)
                ->comment('Code (Snapshot)');

            // WAS wurde gemacht?
            // created, updated, deleted, toggled_active, used, expired
            $table->string('action', 50)
                ->comment('Art der Aktion');

            // DETAILS: Was wurde genau geändert?
            // JSON Format: {
            //   "discount_type": {"old": "percentage", "new": "fixed"},
            //   "discount_value": {"old": 50, "new": 10},
            //   "used_by": "user@example.com",
            //   "plan": "Pro"
            // }
            $table->json('changes')
                ->nullable()
                ->comment('Details der Änderungen (JSON)');

            // Bei "used": Welcher User hat ihn verwendet?
            $table->foreignId('used_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User der den Code verwendet hat (nur bei action=used)');

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
            $table->index('promo_code_id');
            $table->index('used_by_user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_activity_logs');
    }
};
