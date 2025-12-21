<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * SCHEDULED TASKS
 * ===============
 *
 * Grace Period Cleanup:
 * - Läuft täglich um 02:00 Uhr
 * - Bereinigt abgelaufene Grace Periods
 * - Sendet Benachrichtigungen an User
 */
Schedule::command('subscription:cleanup-grace-periods')
    ->dailyAt('02:00')
    ->timezone('Europe/Berlin')
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@example.com'));

/**
 * Review Sentiment-Analyse:
 * - Läuft alle 6 Stunden
 * - Analysiert nur neue Reviews (die noch keine Sentiments haben)
 * - Verwendet AI (OpenAI/Ollama) um Kategorien und Sentiments zu extrahieren
 * - withoutOverlapping verhindert dass es parallel läuft
 * - Bei 500 Reviews werden sie nach und nach alle 6h analysiert
 */
Schedule::command('reviews:analyze-sentiments --all')
    ->everySixHours()
    ->timezone('Europe/Berlin')
    ->withoutOverlapping() // Verhindert parallele Ausführungen
    ->runInBackground(); // Läuft im Hintergrund

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
