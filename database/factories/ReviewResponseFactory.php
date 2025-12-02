<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * REVIEW RESPONSE FACTORY
 * ========================
 *
 * Erstellt Test-Antworten auf Reviews für Unit/Feature Tests
 *
 * Standard-Response:
 * - Gehört zu einem Review
 * - Erstellt von einem User
 * - Zufälliger Antwort-Text
 * - Gesendet an Plattform
 */
class ReviewResponseFactory extends Factory
{
    protected $model = ReviewResponse::class;

    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'user_id' => User::factory(),
            'text' => $this->faker->paragraph(),
            'sent_at' => now(),
            'provider_response_id' => $this->faker->uuid(),
            'metadata' => [],
        ];
    }

    /**
     * Pending (noch nicht gesendet) State
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'sent_at' => null,
            'provider_response_id' => null,
        ]);
    }
}
