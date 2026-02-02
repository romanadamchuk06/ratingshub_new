<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Stripe\StripeClient;

/**
 * STRIPE PRODUCTS SETUP
 *
 * Erstellt Stripe Products und Prices für alle Subscription-Pläne.
 *
 * WICHTIG:
 * - Jeder Plan braucht eine Stripe Price ID
 * - Monatliche Pläne: interval = 'month'
 * - Jährliche Pläne: interval = 'year'
 *
 * USAGE:
 * php artisan stripe:setup-products
 */
class SetupStripeProducts extends Command
{
    protected $signature = 'stripe:setup-products';
    protected $description = 'Create Stripe products and prices for subscription plans';

    public function handle()
    {
        $this->info('Setting up Stripe products and prices...');
        $this->newLine();

        // Prüfe ob Stripe konfiguriert ist
        $stripeSecret = config('cashier.secret');
        if (!$stripeSecret || str_starts_with($stripeSecret, 'sk_test_51SPk')) {
            $this->error('Bitte STRIPE_SECRET in .env konfigurieren!');
            return Command::FAILURE;
        }

        $stripe = new StripeClient($stripeSecret);

        // Hole alle bezahlten Pläne (price > 0) die noch keine Stripe ID haben
        $plans = Plan::where('price', '>', 0)
            ->whereNull('stripe_plan_id')
            ->where('is_active', true)
            ->get();

        if ($plans->isEmpty()) {
            $this->info('Keine Pläne ohne Stripe ID gefunden.');
            $this->newLine();
            $this->showExistingPlans();
            return Command::SUCCESS;
        }

        $this->info("Gefunden: {$plans->count()} Pläne ohne Stripe ID");
        $this->newLine();

        foreach ($plans as $plan) {
            $this->processPlan($stripe, $plan);
        }

        $this->newLine();
        $this->info('✓ Stripe Setup abgeschlossen!');
        $this->newLine();
        $this->showExistingPlans();

        return Command::SUCCESS;
    }

    /**
     * Erstellt Stripe Product und Price für einen Plan
     */
    private function processPlan(StripeClient $stripe, Plan $plan): void
    {
        $interval = $plan->billing_interval === 'yearly' ? 'year' : 'month';
        $intervalLabel = $plan->billing_interval === 'yearly' ? 'Jährlich' : 'Monatlich';

        $this->info("→ {$plan->name} ({$intervalLabel}, {$plan->price}€)");

        try {
            // 1. Stripe Product erstellen
            $product = $stripe->products->create([
                'name' => "{$plan->name} ({$intervalLabel})",
                'description' => $plan->description ?? "RatingsHub {$plan->name} Plan",
                'metadata' => [
                    'plan_id' => $plan->id,
                    'billing_interval' => $plan->billing_interval,
                    'max_platforms' => $plan->max_platforms,
                ],
            ]);

            $this->line("  ✓ Product: {$product->id}");

            // 2. Stripe Price erstellen (recurring)
            $price = $stripe->prices->create([
                'product' => $product->id,
                'unit_amount' => (int) ($plan->price * 100), // Cents
                'currency' => config('cashier.currency', 'eur'),
                'recurring' => [
                    'interval' => $interval, // 'month' oder 'year'
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                ],
            ]);

            $this->line("  ✓ Price: {$price->id}");

            // 3. Plan mit Stripe Price ID updaten
            $plan->update(['stripe_plan_id' => $price->id]);

            $this->line("  ✓ Plan updated");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("  ✗ Fehler: {$e->getMessage()}");
            $this->newLine();
        }
    }

    /**
     * Zeigt alle Pläne mit Stripe IDs
     */
    private function showExistingPlans(): void
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('billing_interval')
            ->orderBy('price')
            ->get();

        $this->table(
            ['ID', 'Name', 'Preis', 'Interval', 'Stripe Price ID'],
            $plans->map(fn($p) => [
                $p->id,
                $p->name,
                number_format($p->price, 2) . ' €',
                $p->billing_interval,
                $p->stripe_plan_id ?? '❌ Fehlt',
            ])
        );
    }
}
