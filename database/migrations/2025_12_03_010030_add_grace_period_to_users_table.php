<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Grace Period = Zeit nach fehlgeschlagener Zahlung, in der User noch Zugriff hat
     *
     * FLOW:
     * 1. Zahlung schlägt fehl
     * 2. ends_grace_period_at = now() + 3 Tage
     * 3. User hat noch 3 Tage Zugriff
     * 4. Nach 3 Tagen: Kein Zugriff mehr, außer neue Zahlung kommt
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ends_grace_period_at')->nullable()->after('trial_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ends_grace_period_at');
        });
    }
};
