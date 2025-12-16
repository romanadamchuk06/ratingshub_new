<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fügt billing_interval zu plans table hinzu
     * Ermöglicht monatliche UND jährliche Abrechnung
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // billing_interval: 'monthly' oder 'yearly'
            $table->enum('billing_interval', ['monthly', 'yearly'])
                ->default('monthly')
                ->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });
    }
};
