<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * DATABASE SEEDER
     * ===============
     *
     * Seed die Datenbank mit Grunddaten:
     * 1. Pläne (Free, Pro, Enterprise)
     * 2. Admin-User (für erste Anmeldung)
     *
     * Ausführen mit: php artisan db:seed
     * Oder bei migrate:fresh: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // 1. Pläne erstellen (Free, Pro, Enterprise)
        $this->call([
            PlanSeeder::class,
        ]);

        // 2. Admin-User erstellen (nur wenn keiner existiert)
        if (!User::where('email', 'admin@ratingshub.de')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@ratingshub.de',
                'password' => Hash::make('changeme123'), // SOFORT ÄNDERN!
                'email_verified_at' => now(),
                'is_admin' => true,
                'plan_id' => null, // Admin braucht kein Abo
            ]);
        }
    }
}
