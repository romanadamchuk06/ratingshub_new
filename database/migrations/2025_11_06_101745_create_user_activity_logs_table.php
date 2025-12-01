<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * USER ACTIVITY LOGS MIGRATION
     * =============================
     *
     * Diese Tabelle loggt ALLE Änderungen an Benutzern:
     *
     * Geloggte Aktionen:
     * - Benutzer erstellt (Registration)
     * - Profil bearbeitet (Name, Email)
     * - Plan gewechselt (Free -> Pro, Pro -> Basic, etc.)
     * - Admin-Status geändert (User -> Admin, Admin -> User)
     * - Passwort geändert
     * - Email verifiziert
     * - 2FA aktiviert/deaktiviert
     * - Benutzer gelöscht
     *
     * Zweck:
     * - Nachvollziehbarkeit: Wer hat was wann geändert?
     * - Compliance: DSGVO-Anforderungen
     * - Security: Verdächtige Aktivitäten erkennen
     * - Support: Bei User-Problemen Historie ansehen
     */
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();

            // WER hat die Aktion durchgeführt?
            // NULL = System (z.B. automatische Registration)
            $table->foreignId('performed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User der die Aktion durchgeführt hat (NULL = System)');

            // An WEM wurde die Aktion durchgeführt?
            $table->foreignId('target_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User an dem die Aktion durchgeführt wurde');

            // WAS wurde gemacht?
            // Mögliche Werte: created, updated, deleted, plan_changed, admin_toggled, etc.
            $table->string('action', 50)
                ->comment('Art der Aktion (created, updated, deleted, plan_changed, etc.)');

            // DETAILS: Was wurde genau geändert?
            // JSON Format: {"field": "name", "old": "Max", "new": "Maximilian"}
            // oder: {"old_plan": "Free", "new_plan": "Pro"}
            $table->json('changes')
                ->nullable()
                ->comment('Details der Änderungen (JSON mit old/new values)');

            // IP-Adresse für Security-Tracking
            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP-Adresse des Akteurs');

            // User-Agent für besseres Tracking
            $table->string('user_agent', 255)
                ->nullable()
                ->comment('Browser/Client des Akteurs');

            // Optionale Beschreibung/Notiz
            $table->text('description')
                ->nullable()
                ->comment('Optionale Beschreibung der Aktion');

            $table->timestamp('created_at')
                ->comment('Zeitpunkt der Aktion');

            // Indices für Performance
            $table->index('performed_by_user_id');
            $table->index('target_user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
