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
 * - Analysiert neue Reviews (die noch keine Sentiments haben)
 * - Verwendet AI (OpenAI/Ollama) um Kategorien und Sentiments zu extrahieren
 */
Schedule::command('reviews:analyze-sentiments --all')
    ->everySixHours()
    ->timezone('Europe/Berlin')
    ->withoutOverlapping() // Verhindert parallele Ausführungen
    ->runInBackground(); // Läuft im Hintergrund, blockiert nicht den Scheduler

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
