<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review_sentiments', function (Blueprint $table) {
            $table->id();

            // Beziehung zum Review
            $table->foreignId('review_id')
                ->constrained()
                ->onDelete('cascade');

            // Kategorie (service, quality, price, etc.)
            $table->string('category');

            // Sentiment (positive, neutral, negative)
            $table->enum('sentiment', ['positive', 'neutral', 'negative']);

            // Konfidenz-Score (0.0 - 1.0) - wie sicher ist die AI?
            $table->decimal('confidence', 3, 2)->default(0.0);

            // Optionaler Textausschnitt, der das Sentiment belegt
            $table->text('excerpt')->nullable();

            $table->timestamps();

            // Index für schnellere Abfragen
            $table->index(['review_id', 'category']);
            $table->index(['category', 'sentiment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_sentiments');
    }
};
