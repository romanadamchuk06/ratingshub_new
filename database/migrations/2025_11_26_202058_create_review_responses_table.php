<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Diese Tabelle speichert Antworten auf Reviews.
     * User können auf Reviews antworten, die dann an die Plattform gesendet werden.
     */
    public function up(): void
    {
        Schema::create('review_responses', function (Blueprint $table) {
            $table->id();

            // Beziehung: Zu welchem Review gehört diese Antwort
            $table->foreignId('review_id')->constrained()->onDelete('cascade');

            // Beziehung: Welcher User hat die Antwort geschrieben
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Antwort-Text
            $table->text('text');

            // Wann wurde die Antwort an die Plattform gesendet (null = noch nicht gesendet)
            $table->timestamp('sent_at')->nullable();

            // Provider Response ID (z.B. Google Response ID, nullable falls noch nicht gesendet)
            $table->string('provider_response_id')->nullable();

            // Zusätzliche Metadaten
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_responses');
    }
};
