<?php

namespace App\Console\Commands;

use App\Models\ConnectedPlatform;
use App\Models\Review;
use App\Services\GoogleMyBusinessService;
use Illuminate\Console\Command;

/**
 * Synchronisiert automatisch Reviews von allen verbundenen Plattformen
 *
 * Läuft als Scheduled Task (z.B. alle 30 Minuten):
 * - Holt alle aktiven ConnectedPlatforms
 * - Ruft für jede Plattform neue Reviews ab (Google My Business API)
 * - Speichert neue Reviews in der Datenbank
 *
 * Usage:
 * php artisan reviews:sync
 */
class SyncReviews extends Command
{
    protected $signature = 'reviews:sync
                            {--platform= : Nur eine spezifische Plattform synchronisieren (ID)}';

    protected $description = 'Synchronisiert Reviews von allen verbundenen Plattformen (Google, etc.)';

    public function handle()
    {
        $this->info('🔄 Review-Synchronisierung gestartet...');
        $this->newLine();

        // Spezifische Plattform oder alle?
        if ($platformId = $this->option('platform')) {
            $platforms = ConnectedPlatform::where('id', $platformId)
                ->where('is_active', true)
                ->get();

            if ($platforms->isEmpty()) {
                $this->error("❌ Plattform #{$platformId} nicht gefunden oder inaktiv.");
                return 1;
            }
        } else {
            // Alle aktiven Plattformen aller User
            $platforms = ConnectedPlatform::where('is_active', true)->get();
        }

        if ($platforms->isEmpty()) {
            $this->info('ℹ️  Keine aktiven Plattformen gefunden.');
            return 0;
        }

        $this->info("📊 {$platforms->count()} aktive Plattformen gefunden.");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($platforms->count());
        $progressBar->start();

        $totalNewReviews = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($platforms as $platform) {
            try {
                $newReviewsCount = $this->syncPlatform($platform);
                $totalNewReviews += $newReviewsCount;
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $this->newLine();
                $this->error("❌ Fehler bei Plattform #{$platform->id} ({$platform->provider}): {$e->getMessage()}");

                \Log::error('Review Sync fehlgeschlagen (Scheduled)', [
                    'platform_id' => $platform->id,
                    'user_id' => $platform->user_id,
                    'provider' => $platform->provider,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Zusammenfassung
        $this->info("✅ Synchronisierung abgeschlossen!");
        $this->info("   Erfolgreich: {$successCount}");
        $this->info("   Neue Reviews: {$totalNewReviews}");

        if ($failedCount > 0) {
            $this->warn("   Fehler: {$failedCount}");
        }

        return 0;
    }

    /**
     * Synchronisiert eine einzelne Plattform
     */
    private function syncPlatform(ConnectedPlatform $platform): int
    {
        $newReviewsCount = 0;

        if ($platform->provider === 'google') {
            $service = app(GoogleMyBusinessService::class);
            $newReviewsCount = $service->fetchReviews($platform);
        } elseif ($platform->provider === 'trustpilot') {
            // TODO: Trustpilot API Integration
            throw new \Exception('Trustpilot Integration noch nicht verfügbar.');
        } else {
            throw new \Exception('Unbekannter Provider: ' . $platform->provider);
        }

        return $newReviewsCount;
    }
}
