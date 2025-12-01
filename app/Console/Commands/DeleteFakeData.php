<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteFakeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fake:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht alle Fake-Daten (Standorte, Reviews, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Lösche Fake-Daten...');

        // Fake Connected Platforms löschen (mit "fake_" prefix)
        $deletedPlatforms = DB::table('connected_platforms')
            ->where('provider_id', 'like', 'fake_%')
            ->delete();

        $this->info("✅ {$deletedPlatforms} Fake-Standorte gelöscht");

        // Falls Reviews-Tabelle existiert, lösche Fake-Reviews
        try {
            $deletedReviews = DB::table('reviews')
                ->where('external_id', 'like', 'fake_%')
                ->delete();

            $this->info("✅ {$deletedReviews} Fake-Reviews gelöscht");
        } catch (\Exception $e) {
            // Reviews-Tabelle existiert nicht, ignorieren
        }

        $this->info('🎉 Fertig! Alle Fake-Daten wurden gelöscht.');
    }
}
