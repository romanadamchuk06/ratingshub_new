<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeReviewSentimentJob;
use App\Models\Review;
use App\Services\ReviewSentimentAnalyzer;
use Illuminate\Console\Command;

/**
 * Analysiert Reviews und extrahiert Sentiments für verschiedene Kategorien
 *
 * WICHTIG: Dieses Command dispatcht Queue Jobs!
 * - Jeder Review wird als separater Job in die Queue gestellt
 * - Der Queue Worker verarbeitet sie dann nacheinander
 * - Nicht blockierend, automatische Retries, parallele Verarbeitung
 *
 * Usage:
 * - php artisan reviews:analyze-sentiments --all      # Alle Reviews ohne Sentiments analysieren
 * - php artisan reviews:analyze-sentiments --review=1 # Spezifisches Review analysieren
 * - php artisan reviews:analyze-sentiments --force    # Alle Reviews neu analysieren
 * - php artisan reviews:analyze-sentiments --sync     # Synchron ausführen (ohne Queue)
 */
class AnalyzeReviewSentiments extends Command
{
    protected $signature = 'reviews:analyze-sentiments
                            {--all : Analysiere alle Reviews ohne Sentiments}
                            {--review= : ID eines spezifischen Reviews}
                            {--force : Re-analysiere auch Reviews mit bestehenden Sentiments}
                            {--sync : Synchron ausführen (ohne Queue, direkt analysieren)}';

    protected $description = 'Analysiert Reviews mit AI und extrahiert Sentiments (via Queue Jobs)';

    public function handle(ReviewSentimentAnalyzer $analyzer)
    {
        $this->info('🔍 Review Sentiment-Analyse gestartet...');
        $this->newLine();

        $useQueue = !$this->option('sync'); // Default: Queue nutzen, außer --sync ist gesetzt

        // Spezifisches Review analysieren
        if ($reviewId = $this->option('review')) {
            $review = Review::find($reviewId);

            if (!$review) {
                $this->error("❌ Review mit ID {$reviewId} nicht gefunden.");
                return 1;
            }

            if ($useQueue) {
                $this->info("Stelle Review #{$review->id} in die Queue...");
                AnalyzeReviewSentimentJob::dispatch($review);
                $this->info("✅ Job wurde in die Queue gestellt!");
            } else {
                $this->info("Analysiere Review #{$review->id} (synchron)...");
                $sentiments = $analyzer->analyze($review);
                $count = count($sentiments);
                $this->info("✅ {$count} Sentiments gefunden!");
                $this->displaySentiments($sentiments);
            }

            return 0;
        }

        // Alle Reviews analysieren (oder nur ohne Sentiments)
        if ($this->option('all') || $this->option('force')) {
            if ($this->option('force')) {
                $reviews = Review::whereNotNull('text')->get();
                $this->warn('⚠️  Force-Mode: Re-analysiere ALLE Reviews!');
            } else {
                $reviews = Review::whereDoesntHave('sentiments')
                    ->whereNotNull('text')
                    ->get();
            }

            if ($reviews->isEmpty()) {
                $this->info('✅ Keine Reviews zum Analysieren gefunden.');
                return 0;
            }

            $this->info("📊 {$reviews->count()} Reviews gefunden.");
            $this->newLine();

            if ($useQueue) {
                // Queue-Modus: Dispatch Jobs
                $this->info('🚀 Stelle Jobs in die Queue...');
                $progressBar = $this->output->createProgressBar($reviews->count());
                $progressBar->start();

                foreach ($reviews as $review) {
                    AnalyzeReviewSentimentJob::dispatch($review);
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine(2);
                $this->info("✅ {$reviews->count()} Jobs wurden in die Queue gestellt!");
                $this->info("   Der Queue Worker wird sie jetzt verarbeiten.");
                $this->newLine();
                $this->comment("💡 Tipp: Überwache den Fortschritt mit: php artisan queue:work");
            } else {
                // Sync-Modus: Direkt analysieren (alte Methode)
                $this->warn('⚠️  Sync-Modus: Analysiere direkt (blockierend!)');
                $progressBar = $this->output->createProgressBar($reviews->count());
                $progressBar->start();

                $analyzed = 0;
                $failed = 0;

                foreach ($reviews as $review) {
                    try {
                        $analyzer->analyze($review);
                        $analyzed++;
                    } catch (\Exception $e) {
                        $failed++;
                        $this->newLine();
                        $this->error("Fehler bei Review #{$review->id}: {$e->getMessage()}");
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine(2);

                $this->info("✅ Analyse abgeschlossen!");
                $this->info("   Erfolgreich: {$analyzed}");
                if ($failed > 0) {
                    $this->warn("   Fehler: {$failed}");
                }
            }

            return 0;
        }

        // Keine Option angegeben
        $this->warn('⚠️  Bitte wähle eine Option:');
        $this->line('   --all           Analysiere alle Reviews ohne Sentiments (via Queue)');
        $this->line('   --review=ID     Analysiere spezifisches Review (via Queue)');
        $this->line('   --force         Re-analysiere alle Reviews (via Queue)');
        $this->line('   --sync          Synchron ausführen (ohne Queue, blockierend)');

        return 1;
    }

    /**
     * Zeigt Sentiments in der Console an
     */
    private function displaySentiments(array $sentiments)
    {
        if (empty($sentiments)) {
            $this->warn('Keine Sentiments gefunden.');
            return;
        }

        $this->newLine();

        foreach ($sentiments as $sentiment) {
            $icon = $sentiment->isPositive() ? '👍' : ($sentiment->isNegative() ? '👎' : '➖');
            $color = $sentiment->isPositive() ? 'info' : ($sentiment->isNegative() ? 'error' : 'comment');

            $categoryDetails = $sentiment->getCategoryDetails();
            $categoryName = $categoryDetails['name'] ?? $sentiment->category;

            $this->line("{$icon} <{$color}>{$categoryName}: {$sentiment->sentiment}</{$color}> (Konfidenz: {$sentiment->confidence})");

            if ($sentiment->excerpt) {
                $this->line("   \"{$sentiment->excerpt}\"");
            }
        }
    }
}
