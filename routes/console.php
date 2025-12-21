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
 * - DEAKTIVIERT: Wird manuell ausgeführt oder via Custom Command in Laravel Cloud
 * - Analysiert neue Reviews (die noch keine Sentiments haben)
 * - Verwendet AI (OpenAI/Ollama) um Kategorien und Sentiments zu extrahieren
 *
 * WICHTIG: Nicht als Scheduled Task nutzen! Führe es manuell aus:
 * php artisan reviews:analyze-sentiments --all
 */
// Schedule::command('reviews:analyze-sentiments --all')
//     ->everySixHours()
//     ->timezone('Europe/Berlin')
//     ->withoutOverlapping()
//     ->runInBackground();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
