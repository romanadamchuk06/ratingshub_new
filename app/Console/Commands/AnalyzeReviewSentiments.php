<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Services\ReviewSentimentAnalyzer;
use Illuminate\Console\Command;

/**
 * Analysiert Reviews und extrahiert Sentiments für verschiedene Kategorien
 *
 * Usage:
 * - php artisan reviews:analyze-sentiments --all      # Alle Reviews ohne Sentiments analysieren
 * - php artisan reviews:analyze-sentiments --review=1 # Spezifisches Review analysieren
 * - php artisan reviews:analyze-sentiments --force    # Alle Reviews neu analysieren
 */
class AnalyzeReviewSentiments extends Command
{
    protected $signature = 'reviews:analyze-sentiments
                            {--all : Analysiere alle Reviews ohne Sentiments}
                            {--review= : ID eines spezifischen Reviews}
                            {--force : Re-analysiere auch Reviews mit bestehenden Sentiments}';

    protected $description = 'Analysiert Reviews mit AI und extrahiert Sentiments für verschiedene Kategorien';

    public function handle(ReviewSentimentAnalyzer $analyzer)
    {
        $this->info('🔍 Review Sentiment-Analyse gestartet...');
        $this->newLine();

        // Spezifisches Review analysieren
        if ($reviewId = $this->option('review')) {
            $review = Review::find($reviewId);

            if (!$review) {
                $this->error("❌ Review mit ID {$reviewId} nicht gefunden.");
                return 1;
            }

            $this->info("Analysiere Review #{$review->id}...");
            $sentiments = $analyzer->analyze($review);

            $count = count($sentiments);
            $this->info("✅ {$count} Sentiments gefunden!");
            $this->displaySentiments($sentiments);

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

            return 0;
        }

        // Keine Option angegeben
        $this->warn('⚠️  Bitte wähle eine Option:');
        $this->line('   --all           Analysiere alle Reviews ohne Sentiments');
        $this->line('   --review=ID     Analysiere spezifisches Review');
        $this->line('   --force         Re-analysiere alle Reviews');

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
