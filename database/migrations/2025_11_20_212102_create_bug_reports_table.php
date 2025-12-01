<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BUG REPORTS TABLE
 * =================
 *
 * User können Bugs melden mit:
 * - Titel & Beschreibung
 * - Kategorie (Bug, Feature Request, etc.)
 * - Priorität (Low, Medium, High)
 * - Status-Tracking (open, in_progress, resolved, closed)
 * - Browser & OS Info
 *
 * WARUM?
 * - User-Feedback sammeln
 * - Bugs tracken und priorisieren
 * - Kommunikation mit Usern über Status
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();

            // User der den Bug gemeldet hat
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Bug Info
            $table->string('title')->comment('Kurzer Titel des Bugs');
            $table->text('description')->comment('Detaillierte Beschreibung');
            $table->enum('type', ['bug', 'feature', 'improvement', 'question'])
                ->default('bug')
                ->comment('Art des Reports');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                ->default('medium')
                ->comment('Priorität');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])
                ->default('open')
                ->comment('Bearbeitungs-Status');

            // Technische Details
            $table->string('page_url')->nullable()->comment('URL wo Bug auftrat');
            $table->string('browser')->nullable()->comment('Browser (Chrome, Firefox, etc.)');
            $table->string('os')->nullable()->comment('Betriebssystem');
            $table->text('steps_to_reproduce')->nullable()->comment('Schritte zum Reproduzieren');

            // Admin Notes
            $table->text('admin_notes')->nullable()->comment('Interne Notizen (nur für Admins)');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Zugewiesener Admin');
            $table->timestamp('resolved_at')->nullable()->comment('Wann wurde Bug gelöst?');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
