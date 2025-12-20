<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\ReviewSentimentAnalyzer;

/**
 * Review Observer
 *
 * Führt automatisch Aktionen aus, wenn Reviews erstellt/aktualisiert werden
 * - Automatische Sentiment-Analyse bei neuen Reviews
 */
class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     *
     * Automatische Sentiment-Analyse für neue Reviews
     * Läuft asynchron, damit der Review-Import nicht blockiert wird
     */
    public function created(Review $review): void
    {
        // Nur analysieren wenn Review einen Text hat
        if (!empty($review->text)) {
            // Asynchron analysieren (läuft im Hintergrund via Queue)
            dispatch(function () use ($review) {
                try {
                    $analyzer = app(ReviewSentimentAnalyzer::class);
                    $analyzer->analyze($review);

                    \Log::info('Auto-Sentiment-Analyse abgeschlossen', [
                        'review_id' => $review->id,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Auto-Sentiment-Analyse fehlgeschlagen', [
                        'review_id' => $review->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse(); // Läuft NACH der HTTP-Response
        }
    }

    /**
     * Handle the Review "updated" event.
     *
     * Re-analysieren wenn der Review-Text geändert wurde
     */
    public function updated(Review $review): void
    {
        // Nur re-analysieren wenn der Text sich geändert hat
        if ($review->isDirty('text') && !empty($review->text)) {
            dispatch(function () use ($review) {
                try {
                    $analyzer = app(ReviewSentimentAnalyzer::class);
                    $analyzer->analyze($review);

                    \Log::info('Auto-Sentiment-Re-Analyse abgeschlossen', [
                        'review_id' => $review->id,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Auto-Sentiment-Re-Analyse fehlgeschlagen', [
                        'review_id' => $review->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        // Sentiments werden automatisch per Cascade gelöscht (siehe Migration)
    }

    /**
     * Handle the Review "restored" event.
     */
    public function restored(Review $review): void
    {
        // Neu analysieren nach Wiederherstellung
        if (!empty($review->text)) {
            dispatch(function () use ($review) {
                try {
                    $analyzer = app(ReviewSentimentAnalyzer::class);
                    $analyzer->analyze($review);
                } catch (\Exception $e) {
                    \Log::error('Auto-Sentiment-Analyse nach Restore fehlgeschlagen', [
                        'review_id' => $review->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }
    }

    /**
     * Handle the Review "force deleted" event.
     */
    public function forceDeleted(Review $review): void
    {
        // Sentiments werden automatisch per Cascade gelöscht
    }
}
