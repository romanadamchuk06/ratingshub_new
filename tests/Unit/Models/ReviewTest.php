<?php

namespace Tests\Unit\Models;

use App\Models\ConnectedPlatform;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REVIEW MODEL TESTS
 * ==================
 *
 * Testet das Review Model:
 * - Relationships (user, connectedPlatform, responses)
 * - Metadata als Array
 * - Review Date als DateTime
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Review gehört zu einem User
     */
    public function test_review_belongs_to_user()
    {
        // Arrange
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        // Act & Assert
        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    /**
     * Test: Review gehört zu einer ConnectedPlatform
     */
    public function test_review_belongs_to_connected_platform()
    {
        // Arrange
        $platform = ConnectedPlatform::factory()->create();
        $review = Review::factory()->create(['connected_platform_id' => $platform->id]);

        // Act & Assert
        $this->assertInstanceOf(ConnectedPlatform::class, $review->connectedPlatform);
        $this->assertEquals($platform->id, $review->connectedPlatform->id);
    }

    /**
     * Test: Review kann mehrere Responses haben
     */
    public function test_review_has_many_responses()
    {
        // Arrange
        $review = Review::factory()->create();
        ReviewResponse::factory()->count(2)->create(['review_id' => $review->id]);

        // Act & Assert
        $this->assertCount(2, $review->responses);
        $this->assertInstanceOf(ReviewResponse::class, $review->responses->first());
    }

    /**
     * Test: Review castet metadata zu Array
     */
    public function test_review_casts_metadata_to_array()
    {
        // Arrange & Act
        $review = Review::factory()->create([
            'metadata' => [
                'source' => 'google',
                'location' => 'Berlin',
                'verified' => true,
            ],
        ]);

        // Assert
        $this->assertIsArray($review->metadata);
        $this->assertEquals('google', $review->metadata['source']);
        $this->assertEquals('Berlin', $review->metadata['location']);
        $this->assertTrue($review->metadata['verified']);
    }

    /**
     * Test: Review castet review_date zu DateTime
     */
    public function test_review_casts_review_date_to_datetime()
    {
        // Arrange & Act
        $review = Review::factory()->create([
            'review_date' => '2024-01-15 10:30:00',
        ]);

        // Assert
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $review->review_date);
    }

    /**
     * Test: Review hat alle notwendigen Felder
     */
    public function test_review_has_required_fields()
    {
        // Arrange & Act
        $review = Review::factory()->create([
            'rating' => 5,
            'text' => 'Excellent service!',
            'reviewer_name' => 'John Doe',
            'status' => 'pending',
        ]);

        // Assert
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Excellent service!', $review->text);
        $this->assertEquals('John Doe', $review->reviewer_name);
        $this->assertEquals('pending', $review->status);
    }
}
