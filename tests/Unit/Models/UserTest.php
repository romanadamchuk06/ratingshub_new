<?php

namespace Tests\Unit\Models;

use App\Models\ConnectedPlatform;
use App\Models\Plan;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * USER MODEL TESTS
 * ================
 *
 * Testet alle wichtigen Methods des User Models:
 * - Trial-Management (onTrial, trialExpired, startTrial)
 * - Platform-Limits (canAddPlatform, remainingPlatformSlots)
 * - Feature-Checks (canUseAI, hasPrioritySupport)
 * - Review-Limits (getReviewLimit, remainingReviews)
 * - Subscription-Status (hasActiveSubscription)
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User kann auf Trial sein
     */
    public function test_user_can_be_on_trial()
    {
        // Arrange: User mit zukünftigem Trial-Ende erstellen
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(7),
        ]);

        // Act & Assert
        $this->assertTrue($user->onTrial());
        $this->assertFalse($user->trialExpired());
    }

    /**
     * Test: User Trial kann abgelaufen sein
     */
    public function test_user_trial_can_be_expired()
    {
        // Arrange: User mit vergangenem Trial-Ende erstellen
        $user = User::factory()->create([
            'trial_ends_at' => now()->subDays(1),
        ]);

        // Act & Assert
        $this->assertFalse($user->onTrial());
        $this->assertTrue($user->trialExpired());
    }

    /**
     * Test: User kann Trial starten
     */
    public function test_user_can_start_trial()
    {
        // Arrange
        $user = User::factory()->create(['trial_ends_at' => null]);

        // Act
        $user->startTrial(30);

        // Assert
        $user->refresh();
        $this->assertTrue($user->onTrial());
        $this->assertNotNull($user->trial_ends_at);
    }

    /**
     * Test: User kann Plattformen hinzufügen bis zum Limit
     */
    public function test_user_can_add_platforms_up_to_limit()
    {
        // Arrange: Plan mit max 2 Plattformen
        $plan = Plan::factory()->create(['max_platforms' => 2]);
        $user = User::factory()->create(['plan_id' => $plan->id]);

        // Act & Assert: 0 Plattformen → kann hinzufügen
        $this->assertTrue($user->canAddPlatform());
        $this->assertEquals(2, $user->remainingPlatformSlots());

        // 1 Plattform hinzufügen (google)
        ConnectedPlatform::factory()->google()->create(['user_id' => $user->id]);
        $user->refresh();

        $this->assertTrue($user->canAddPlatform());
        $this->assertEquals(1, $user->remainingPlatformSlots());

        // 2. Plattform hinzufügen (trustpilot) → Limit erreicht
        ConnectedPlatform::factory()->trustpilot()->create(['user_id' => $user->id]);
        $user->refresh();

        $this->assertFalse($user->canAddPlatform());
        $this->assertEquals(0, $user->remainingPlatformSlots());
    }

    /**
     * Test: Admin hat immer aktive Subscription
     */
    public function test_admin_always_has_active_subscription()
    {
        // Arrange: Admin ohne Abo
        $admin = User::factory()->create([
            'is_admin' => true,
            'plan_id' => null,
            'trial_ends_at' => null,
        ]);

        // Act & Assert
        $this->assertTrue($admin->hasActiveSubscription());
    }

    /**
     * Test: User mit Trial hat aktive Subscription
     */
    public function test_user_with_trial_has_active_subscription()
    {
        // Arrange
        $user = User::factory()->create([
            'is_admin' => false,
            'trial_ends_at' => now()->addDays(7),
        ]);

        // Act & Assert
        $this->assertTrue($user->hasActiveSubscription());
    }

    /**
     * Test: Admin kann AI nutzen
     */
    public function test_admin_can_use_ai()
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);

        // Act & Assert
        $this->assertTrue($admin->canUseAI());
    }

    /**
     * Test: Admin hat unbegrenztes Review-Limit
     */
    public function test_admin_has_unlimited_review_limit()
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);

        // Act & Assert
        $this->assertEquals(PHP_INT_MAX, $admin->getReviewLimit());
        $this->assertEquals(PHP_INT_MAX, $admin->remainingReviews());
    }

    /**
     * Test: User Relationships funktionieren
     */
    public function test_user_relationships()
    {
        // Arrange
        $plan = Plan::factory()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);

        // Zwei verschiedene Provider-Plattformen hinzufügen
        ConnectedPlatform::factory()->google()->create(['user_id' => $user->id]);
        ConnectedPlatform::factory()->trustpilot()->create(['user_id' => $user->id]);

        Review::factory()->count(3)->create(['user_id' => $user->id]);
        ReviewResponse::factory()->count(1)->create(['user_id' => $user->id]);

        // Act & Assert
        $this->assertInstanceOf(Plan::class, $user->plan);
        $this->assertCount(2, $user->connectedPlatforms);
        $this->assertCount(3, $user->reviews);
        $this->assertCount(1, $user->reviewResponses);
    }
}
