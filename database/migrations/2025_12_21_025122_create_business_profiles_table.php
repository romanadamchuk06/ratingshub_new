<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Business Profile Tabelle:
     * - Jeder User hat ein Business Profile (1:1 Beziehung)
     * - Enthält Unternehmensinformationen wie Name, Adresse, Öffnungszeiten, etc.
     * - Kann vom User in den Settings bearbeitet werden
     */
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();

            // Beziehung: Jeder User hat EIN Business Profile
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Grundlegende Unternehmensinformationen
            $table->string('business_name')->nullable(); // Firmenname
            $table->text('description')->nullable(); // Beschreibung/Über uns
            $table->string('industry')->nullable(); // Branche (Restaurant, Hotel, Einzelhandel, etc.)

            // Kontaktinformationen
            $table->string('phone')->nullable();
            $table->string('email')->nullable(); // Geschäftliche E-Mail
            $table->string('website')->nullable();

            // Adresse
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Deutschland');

            // Öffnungszeiten (JSON für Flexibilität)
            // Format: {"monday": {"open": "09:00", "close": "18:00", "closed": false}, ...}
            $table->json('opening_hours')->nullable();

            // Logo/Bild
            $table->string('logo_url')->nullable();

            // Social Media Links
            $table->json('social_links')->nullable(); // Facebook, Instagram, etc.

            // Zusätzliche Metadaten
            $table->json('metadata')->nullable(); // Für zukünftige Erweiterungen

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
