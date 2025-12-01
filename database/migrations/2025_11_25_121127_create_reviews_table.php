<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Diese Tabelle speichert alle Reviews von verschiedenen Plattformen (Google, Trustpilot, etc.)
     * Jeder Review gehört zu einem User und einer verbundenen Plattform.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Beziehung: Welcher User sieht diesen Review (Owner)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Beziehung: Von welcher verbundenen Plattform stammt der Review
            $table->foreignId('connected_platform_id')->constrained()->onDelete('cascade');

            // Plattform-spezifische Review-ID (z.B. Google Review ID)
            $table->string('provider_review_id');

            // Review-Daten
            $table->integer('rating'); // 1-5 Sterne
            $table->text('text')->nullable(); // Review-Text (kann leer sein)

            // Reviewer-Informationen
            $table->string('reviewer_name');
            $table->string('reviewer_photo_url')->nullable();

            // Zeitstempel des Reviews auf der Plattform
            $table->timestamp('review_date');

            // Status: pending (neu), responded (beantwortet), archived (archiviert)
            $table->enum('status', ['pending', 'responded', 'archived'])->default('pending');

            // Zusätzliche Metadaten als JSON (z.B. Location Name, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Unique Constraint: Jeder Review nur einmal pro Plattform
            $table->unique(['connected_platform_id', 'provider_review_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
