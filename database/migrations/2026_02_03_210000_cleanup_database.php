<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DATABASE CLEANUP MIGRATION
 * ==========================
 *
 * Diese Migration räumt die Datenbank auf:
 * 1. Löscht Promo-Code-Tabellen (jetzt in Stripe verwaltet)
 * 2. Entfernt veraltete Spalten aus der Plans-Tabelle
 * 3. Löscht unnötige Test-/Dummy-Pläne
 *
 * WICHTIG: Vor dem Ausführen ein Backup machen!
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Promo-Code-Tabellen löschen (jetzt in Stripe verwaltet)
        Schema::dropIfExists('promo_code_activity_logs');
        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');

        // 2. Veraltete Spalten aus plans-Tabelle entfernen
        Schema::table('plans', function (Blueprint $table) {
            // Diese Spalten werden nicht mehr benötigt:
            // - billing_interval: Stripe Pricing Table handhabt monatlich/jährlich
            // - stripe_plan_id: Ersetzt durch stripe_price_id_monthly/yearly
            // - analytics, priority_support, api_access, white_label: Features sind jetzt im JSON-Array

            $columns = [];

            // Prüfen welche Spalten existieren und entfernen
            if (Schema::hasColumn('plans', 'billing_interval')) {
                $columns[] = 'billing_interval';
            }
            if (Schema::hasColumn('plans', 'stripe_plan_id')) {
                $columns[] = 'stripe_plan_id';
            }
            if (Schema::hasColumn('plans', 'analytics')) {
                $columns[] = 'analytics';
            }
            if (Schema::hasColumn('plans', 'priority_support')) {
                $columns[] = 'priority_support';
            }
            if (Schema::hasColumn('plans', 'api_access')) {
                $columns[] = 'api_access';
            }
            if (Schema::hasColumn('plans', 'white_label')) {
                $columns[] = 'white_label';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        // 3. Alte/Test-Pläne löschen (nur die mit alter Struktur)
        // Behält: free, pro, enterprise
        \App\Models\Plan::whereNotIn('slug', ['free', 'pro', 'enterprise'])->delete();
    }

    public function down(): void
    {
        // Promo-Code-Tabellen wiederherstellen (falls nötig)
        if (!Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('type')->default('percentage');
                $table->decimal('value', 10, 2);
                $table->integer('max_uses')->nullable();
                $table->integer('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Alte Spalten wiederherstellen
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'billing_interval')) {
                $table->string('billing_interval')->default('monthly');
            }
            if (!Schema::hasColumn('plans', 'stripe_plan_id')) {
                $table->string('stripe_plan_id')->nullable();
            }
        });
    }
};
