<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SubscriptionCancelled;
use Illuminate\Console\Command;

/**
 * CLEANUP EXPIRED GRACE PERIODS
 * ==============================
 *
 * Läuft täglich und prüft ob Grace Periods abgelaufen sind.
 *
 * FLOW:
 * 1. Finde alle User mit ends_grace_period_at in der Vergangenheit
 * 2. Prüfe ob sie noch ein aktives Cashier-Abo haben
 * 3. Falls NEIN: Benachrichtigung senden & Grace Period entfernen
 * 4. Falls JA: Grace Period entfernen (Zahlung kam doch noch)
 *
 * SCHEDULE:
 * Täglich um 02:00 Uhr morgens
 */
class CleanupExpiredGracePeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:cleanup-grace-periods
                            {--dry-run : Führe keine Änderungen durch, zeige nur was passieren würde}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bereinigt abgelaufene Grace Periods und benachrichtigt User';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - Keine Änderungen werden durchgeführt');
        }

        // Finde alle User mit abgelaufenen Grace Periods
        $expiredUsers = User::whereNotNull('ends_grace_period_at')
            ->where('ends_grace_period_at', '<=', now())
            ->get();

        $this->info("Gefundene User mit abgelaufener Grace Period: {$expiredUsers->count()}");

        $cancelledCount = 0;
        $restoredCount = 0;

        foreach ($expiredUsers as $user) {
            // Prüfe ob User mittlerweile ein aktives Abo hat
            $hasActiveSubscription = $user->subscribed('default');

            if ($hasActiveSubscription) {
                // User hat wieder bezahlt → Grace Period einfach entfernen
                $this->line("✓ User #{$user->id} ({$user->email}) - Zahlung erfolgt, Grace Period wird entfernt");

                if (!$dryRun) {
                    $user->ends_grace_period_at = null;
                    $user->save();
                }

                $restoredCount++;
            } else {
                // User hat NICHT bezahlt → Benachrichtigung senden
                $this->line("⚠ User #{$user->id} ({$user->email}) - Kein aktives Abo, Benachrichtigung wird gesendet");

                if (!$dryRun) {
                    // Benachrichtigung senden
                    try {
                        $user->notify(new SubscriptionCancelled());
                    } catch (\Exception $e) {
                        $this->error("Fehler beim Senden der Benachrichtigung an User #{$user->id}: {$e->getMessage()}");
                    }

                    // Grace Period entfernen
                    $user->ends_grace_period_at = null;
                    $user->save();
                }

                $cancelledCount++;
            }
        }

        // Zusammenfassung
        $this->newLine();
        $this->info('=== ZUSAMMENFASSUNG ===');
        $this->line("Wiederhergestellt (Zahlung erfolgt): {$restoredCount}");
        $this->line("Gekündigt (Keine Zahlung): {$cancelledCount}");
        $this->line("Gesamt verarbeitet: {$expiredUsers->count()}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN - Führe den Befehl ohne --dry-run aus um die Änderungen zu übernehmen');
        }

        return Command::SUCCESS;
    }
}
