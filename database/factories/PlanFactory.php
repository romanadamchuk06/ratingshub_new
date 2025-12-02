<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * PLAN FACTORY
 * ============
 *
 * Erstellt Test-Pläne für Unit/Feature Tests
 *
 * Standard-Plan:
 * - Aktiv
 * - Nicht "Beliebt"
 * - 2 Plattformen
 * - Zufälliger Preis zwischen 0 und 99.99
 * - Features als Array
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Plan',
            'slug' => $this->faker->unique()->slug(2),
            'stripe_plan_id' => 'price_' . $this->faker->uuid(),
            'price' => $this->faker->randomFloat(2, 0, 99.99),
            'max_platforms' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->sentence(),
            'features' => [
                'ai_responses' => $this->faker->boolean(),
                'multi_user' => $this->faker->boolean(),
                'priority_support' => $this->faker->boolean(),
                'review_limit' => $this->faker->randomElement([50, 100, 500, 1000]),
            ],
        ];
    }

    /**
     * Free Plan State
     */
    public function free(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'stripe_plan_id' => null,
            'max_platforms' => 1,
            'features' => [
                'ai_responses' => false,
                'multi_user' => false,
                'priority_support' => false,
                'review_limit' => 10,
            ],
        ]);
    }

    /**
     * Popular Plan State
     */
    public function popular(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_popular' => true,
        ]);
    }

    /**
     * Inactive Plan State
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
