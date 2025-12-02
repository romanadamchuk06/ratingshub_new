<?php

namespace Database\Factories;

use App\Models\ConnectedPlatform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * CONNECTED PLATFORM FACTORY
 * ===========================
 *
 * Erstellt Test-Plattformen für Unit/Feature Tests
 *
 * Standard-Plattform:
 * - Aktiv
 * - Zufälliger Provider (Google, Trustpilot, etc.)
 * - OAuth Token und Metadata
 */
class ConnectedPlatformFactory extends Factory
{
    protected $model = ConnectedPlatform::class;

    public function definition(): array
    {
        $provider = $this->faker->randomElement(['google', 'trustpilot', 'yelp']);

        return [
            'user_id' => User::factory(),
            'provider' => $provider,
            'provider_id' => $this->faker->uuid(),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'metadata' => [
                'location_name' => $this->faker->company(),
                'address' => $this->faker->address(),
                'phone' => $this->faker->phoneNumber(),
                'website' => $this->faker->url(),
            ],
        ];
    }

    /**
     * Google Provider State
     */
    public function google(): static
    {
        return $this->state(fn(array $attributes) => [
            'provider' => 'google',
            'metadata' => [
                'location_name' => $this->faker->company() . ' - Google My Business',
                'address' => $this->faker->address(),
                'phone' => $this->faker->phoneNumber(),
                'website' => $this->faker->url(),
            ],
        ]);
    }

    /**
     * Trustpilot Provider State
     */
    public function trustpilot(): static
    {
        return $this->state(fn(array $attributes) => [
            'provider' => 'trustpilot',
            'metadata' => [
                'location_name' => $this->faker->company() . ' - Trustpilot',
                'address' => $this->faker->address(),
                'phone' => $this->faker->phoneNumber(),
                'website' => $this->faker->url(),
            ],
        ]);
    }

    /**
     * Inactive Platform State
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
