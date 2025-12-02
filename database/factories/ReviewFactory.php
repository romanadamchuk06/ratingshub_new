<?php

namespace Database\Factories;

use App\Models\ConnectedPlatform;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * REVIEW FACTORY
 * ==============
 *
 * Erstellt Test-Reviews für Unit/Feature Tests
 *
 * Standard-Review:
 * - Rating 1-5 Sterne
 * - Zufälliger Text
 * - Review-Datum in der Vergangenheit
 * - Status: pending
 * - Metadata mit zusätzlichen Infos
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'connected_platform_id' => ConnectedPlatform::factory(),
            'provider_review_id' => $this->faker->uuid(),
            'rating' => $this->faker->numberBetween(1, 5),
            'text' => $this->faker->paragraph(),
            'reviewer_name' => $this->faker->name(),
            'reviewer_photo_url' => $this->faker->imageUrl(200, 200, 'people'),
            'review_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => 'pending',
            'metadata' => [
                'source' => $this->faker->randomElement(['google', 'trustpilot', 'yelp']),
                'location' => $this->faker->city(),
                'verified' => $this->faker->boolean(),
            ],
        ];
    }

    /**
     * 5-Sterne Review State
     */
    public function fiveStars(): static
    {
        return $this->state(fn(array $attributes) => [
            'rating' => 5,
            'text' => $this->faker->randomElement([
                'Excellent service! Highly recommended!',
                'Best experience ever! Will come back again!',
                'Outstanding quality and service!',
            ]),
        ]);
    }

    /**
     * 1-Sterne Review State (negative Review)
     */
    public function oneStar(): static
    {
        return $this->state(fn(array $attributes) => [
            'rating' => 1,
            'text' => $this->faker->randomElement([
                'Very disappointed with the service.',
                'Poor quality, would not recommend.',
                'Worst experience ever.',
            ]),
        ]);
    }

    /**
     * Responded Status State
     */
    public function responded(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'responded',
        ]);
    }

    /**
     * Archived Status State
     */
    public function archived(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
