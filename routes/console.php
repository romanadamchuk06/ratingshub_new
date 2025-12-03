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

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
