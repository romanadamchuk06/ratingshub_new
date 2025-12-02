<?php

namespace Tests\Feature;

use App\Models\ConnectedPlatform;
use App\Models\Plan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REVIEW CONTROLLER FEATURE TESTS
 * ================================
 *
 * Testet den ReviewController:
 * - Index (Liste der Reviews mit Filtern)
 * - Respond (Auf Review antworten)
 * - Update Status (Review-Status ändern)
 */
class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User mit Abo kann Reviews sehen
     */
    public function test_subscribed_user_can_view_reviews()
    {
        // Arrange: User mit aktivem Abo
        $plan = Plan::factory()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);

        // Platform und Reviews erstellen
        $platform = ConnectedPlatform::factory()->create(['user_id' => $user->id]);
        Review::factory()->count(3)->create([
            'user_id' => $user->id,
            'connected_platform_id' => $platform->id,
        ]);

        // Act: Reviews Index aufrufen
        $response = $this->actingAs($user)->get(route('reviews.index'));

        // Assert: Erfolgreicher Response mit Reviews
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Reviews')
            ->has('reviews.data', 3)
        );
    }

    /**
     * Test: User ohne Abo wird zu Subscription-Seite weitergeleitet
     */
    public function test_user_without_subscription_is_redirected()
    {
        // Arrange: User OHNE Abo
        $user = User::factory()->create([
            'plan_id' => null,
            'trial_ends_at' => null,
        ]);

        // Act: Versuche Reviews zu sehen
        $response = $this->actingAs($user)->get(route('reviews.index'));

        // Assert: Redirect zu Subscription
        $response->assertRedirect(route('subscription.index'));
    }

    /**
     * Test: Reviews können nach Status gefiltert werden
     */
    public function test_reviews_can_be_filtered_by_status()
    {
        // Arrange: User mit Abo
        $plan = Plan::factory()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $platform = ConnectedPlatform::factory()->create(['user_id' => $user->id]);

        // Reviews mit verschiedenen Status erstellen
        Review::factory()->create([
            'user_id' => $user->id,
            'connected_platform_id' => $platform->id,
            'status' => 'pending',
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'connected_platform_id' => $platform->id,
            'status' => 'responded',
        ]);

        // Act: Nach "pending" filtern
        $response = $this->actingAs($user)->get(route('reviews.index', ['status' => 'pending']));

        // Assert: Nur pending Reviews
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('reviews.data', 1)
            ->where('filters.status', 'pending')
        );
    }

    /**
     * Test: Reviews können nach Rating gefiltert werden
     */
    public function test_reviews_can_be_filtered_by_rating()
    {
        // Arrange: User mit Abo
        $plan = Plan::factory()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $platform = ConnectedPlatform::factory()->create(['user_id' => $user->id]);

        // Reviews mit verschiedenen Ratings erstellen
        Review::factory()->create([
            'user_id' => $user->id,
            'connected_platform_id' => $platform->id,
            'rating' => 5,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'connected_platform_id' => $platform->id,
            'rating' => 1,
        ]);

        // Act: Nach Rating 5 filtern
        $response = $this->actingAs($user)->get(route('reviews.index', ['rating' => 5]));

        // Assert: Nur 5-Sterne Reviews
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('reviews.data', 1)
            ->where('filters.rating', '5') // Rating wird als String zurückgegeben
        );
    }

    /**
     * Test: User kann Review-Status ändern
     */
    public function test_user_can_update_review_status()
    {
        // Arrange: User mit Abo und Review
        $plan = Plan::factory()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $platform = ConnectedPlatform::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'connected_platform_id' => $platform->id,
            'status' => 'pending',
        ]);

        // Act: Status ändern zu "archived"
        $response = $this->actingAs($user)->patch(route('reviews.update-status', $review), [
            'status' => 'archived',
        ]);

        // Assert: Status wurde geändert
        $response->assertStatus(302); // Redirect back
        $review->refresh();
        $this->assertEquals('archived', $review->status);
    }

    /**
     * Test: User kann nur eigene Reviews sehen
     */
    public function test_user_can_only_see_own_reviews()
    {
        // Arrange: Zwei User mit je eigenen Reviews
        $plan = Plan::factory()->create();
        $user1 = User::factory()->create(['plan_id' => $plan->id]);
        $user2 = User::factory()->create(['plan_id' => $plan->id]);

        $platform1 = ConnectedPlatform::factory()->create(['user_id' => $user1->id]);
        $platform2 = ConnectedPlatform::factory()->create(['user_id' => $user2->id]);

        Review::factory()->count(2)->create([
            'user_id' => $user1->id,
            'connected_platform_id' => $platform1->id,
        ]);
        Review::factory()->count(3)->create([
            'user_id' => $user2->id,
            'connected_platform_id' => $platform2->id,
        ]);

        // Act: User1 ruft Reviews auf
        $response = $this->actingAs($user1)->get(route('reviews.index'));

        // Assert: User1 sieht nur seine 2 Reviews
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->has('reviews.data', 2)
        );
    }
}
