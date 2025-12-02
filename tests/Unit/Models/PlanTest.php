<?php

namespace Tests\Unit\Models;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAN MODEL TESTS
 * ================
 *
 * Testet die Plan Model Methods:
 * - isFree() - Prüft ob Plan kostenlos ist
 * - getFormattedPriceAttribute() - Formatiert Preis
 * - Relationships (users)
 */
class PlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Plan kann kostenlos sein
     */
    public function test_plan_can_be_free()
    {
        // Arrange
        $freePlan = Plan::factory()->create(['price' => 0]);
        $paidPlan = Plan::factory()->create(['price' => 29.99]);

        // Act & Assert
        $this->assertTrue($freePlan->isFree());
        $this->assertFalse($paidPlan->isFree());
    }

    /**
     * Test: Plan formatiert Preis korrekt
     */
    public function test_plan_formats_price_correctly()
    {
        // Arrange
        $plan = Plan::factory()->create(['price' => 29.99]);

        // Act
        $formatted = $plan->formatted_price;

        // Assert
        $this->assertEquals('29.99 €', $formatted);
    }

    /**
     * Test: Plan kann Features als Array speichern
     */
    public function test_plan_casts_features_to_array()
    {
        // Arrange & Act
        $plan = Plan::factory()->create([
            'features' => [
                'ai_responses' => true,
                'multi_user' => false,
                'review_limit' => 100,
            ],
        ]);

        // Assert
        $this->assertIsArray($plan->features);
        $this->assertTrue($plan->features['ai_responses']);
        $this->assertFalse($plan->features['multi_user']);
        $this->assertEquals(100, $plan->features['review_limit']);
    }

    /**
     * Test: Plan hat Beziehung zu Users
     */
    public function test_plan_has_users_relationship()
    {
        // Arrange
        $plan = Plan::factory()->create();
        User::factory()->count(3)->create(['plan_id' => $plan->id]);

        // Act & Assert
        $this->assertCount(3, $plan->users);
        $this->assertInstanceOf(User::class, $plan->users->first());
    }

    /**
     * Test: Plan kann als aktiv/inaktiv markiert werden
     */
    public function test_plan_can_be_active_or_inactive()
    {
        // Arrange
        $activePlan = Plan::factory()->create(['is_active' => true]);
        $inactivePlan = Plan::factory()->create(['is_active' => false]);

        // Act & Assert
        $this->assertTrue($activePlan->is_active);
        $this->assertFalse($inactivePlan->is_active);
    }

    /**
     * Test: Plan kann als beliebt markiert werden
     */
    public function test_plan_can_be_marked_as_popular()
    {
        // Arrange
        $popularPlan = Plan::factory()->create(['is_popular' => true]);
        $normalPlan = Plan::factory()->create(['is_popular' => false]);

        // Act & Assert
        $this->assertTrue($popularPlan->is_popular);
        $this->assertFalse($normalPlan->is_popular);
    }
}
