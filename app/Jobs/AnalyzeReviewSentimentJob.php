<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\ReviewSentimentAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue Job für Sentiment-Analyse eines einzelnen Reviews
 *
 * Dieser Job wird in die Queue gestellt und vom Queue Worker verarbeitet.
 * Jeder Review wird einzeln analysiert, dadurch:
 * - Nicht blockierend
 * - Automatische Retries bei Fehlern
 * - Parallele Verarbeitung möglich
 * - Progress Tracking möglich
 */
class AnalyzeReviewSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximale Anzahl an Versuchen bei Fehlern
     */
    public $tries = 3;

    /**
     * Maximale Zeit die der Job laufen darf (in Sekunden)
     * AI-Analyse kann länger dauern, daher 5 Minuten
     */
    public $timeout = 300;

    /**
     * Der Review der analysiert werden soll
     */
    public function __construct(
        public Review $review
    ) {}

    /**
     * Führt die Sentiment-Analyse aus
     */
    public function handle(ReviewSentimentAnalyzer $analyzer): void
    {
        try {
            // Prüfe ob Review überhaupt Text hat
            if (empty($this->review->text)) {
                Log::info("Review #{$this->review->id} hat keinen Text, überspringe Analyse.");
                return;
            }

            // Analysiere Review
            Log::info("Starte Sentiment-Analyse für Review #{$this->review->id}");
            $sentiments = $analyzer->analyze($this->review);

            Log::info("Review #{$this->review->id} erfolgreich analysiert. " . count($sentiments) . " Sentiments gefunden.");
        } catch (\Exception $e) {
            // Fehler loggen
            Log::error("Fehler bei Sentiment-Analyse für Review #{$this->review->id}: {$e->getMessage()}", [
                'review_id' => $this->review->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Job als fehlgeschlagen markieren (wird automatisch neu versucht wenn $tries > 1)
            throw $e;
        }
    }

    /**
     * Wird aufgerufen wenn der Job nach allen Versuchen fehlschlägt
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Sentiment-Analyse für Review #{$this->review->id} fehlgeschlagen nach {$this->tries} Versuchen.", [
            'review_id' => $this->review->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
