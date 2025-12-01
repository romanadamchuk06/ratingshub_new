<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SetupStripeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:setup-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Stripe products and prices for subscription plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Stripe products and prices...');

        $stripe = new StripeClient(config('cashier.secret'));

        // Get all paid plans (price > 0)
        $plans = Plan::where('price', '>', 0)->get();

        if ($plans->isEmpty()) {
            $this->warn('No paid plans found in database.');
            return Command::FAILURE;
        }

        foreach ($plans as $plan) {
            $this->info("Processing plan: {$plan->name}");

            try {
                // Create Stripe Product
                $product = $stripe->products->create([
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'max_platforms' => $plan->max_platforms,
                    ],
                ]);

                $this->info("  ✓ Product created: {$product->id}");

                // Create Stripe Price (recurring monthly)
                $price = $stripe->prices->create([
                    'product' => $product->id,
                    'unit_amount' => (int) ($plan->price * 100), // Convert to cents
                    'currency' => config('cashier.currency', 'eur'),
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'metadata' => [
                        'plan_id' => $plan->id,
                    ],
                ]);

                $this->info("  ✓ Price created: {$price->id}");

                // Update plan with Stripe price ID
                $plan->update([
                    'stripe_plan_id' => $price->id,
                ]);

                $this->info("  ✓ Plan updated in database with price ID: {$price->id}");
                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  ✗ Error processing plan {$plan->name}: {$e->getMessage()}");
                $this->newLine();
                continue;
            }
        }

        $this->info('✓ Stripe products and prices setup completed!');
        $this->newLine();

        $this->table(
            ['Plan', 'Price', 'Stripe Price ID'],
            $plans->map(fn($plan) => [
                $plan->name,
                number_format($plan->price, 2) . ' €',
                $plan->stripe_plan_id ?? 'Not set',
            ])
        );

        return Command::SUCCESS;
    }
}
