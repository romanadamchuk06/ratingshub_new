<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Deaktiviert den Free Plan.
 *
 * User sollen nur noch Pro oder Business wählen können.
 * Bestehende Free-User behalten ihren Plan, können aber nicht neu gewählt werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Free Plan deaktivieren (nicht löschen, falls User noch darauf sind)
        DB::table('plans')
            ->where('slug', 'free')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Free Plan wieder aktivieren
        DB::table('plans')
            ->where('slug', 'free')
            ->update(['is_active' => true]);
    }
};
