<?php

namespace Tests\Unit;

use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for Review model
 *
 * TDD Approach:
 * - Write tests FIRST describing expected behavior
 * - Tests will FAIL initially (Review model doesn't exist yet)
 * - Implement model ONLY to pass these tests
 *
 * NAVER API Integration Notes:
 * - Review comments can be translated via PapagoService
 * - Translation is optional and triggered by API request
 * - Model stores original comment; translations stored in Translation model
 */
class ReviewModelTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_can_be_created_with_required_attributes(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
            'comment' => 'Great place to visit!',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_has_correct_fillable_attributes(): void
    {
        $review = new Review();

        $fillable = $review->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('reviewable_type', $fillable);
        $this->assertContains('reviewable_id', $fillable);
        $this->assertContains('rating', $fillable);
        $this->assertContains('comment', $fillable);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_has_polymorphic_reviewable_relationship(): void
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $this->assertInstanceOf(Place::class, $review->reviewable);
        $this->assertEquals($place->id, $review->reviewable->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_can_be_for_place(): void
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $this->assertEquals(Place::class, $review->reviewable_type);
        $this->assertEquals($place->id, $review->reviewable_id);
        $this->assertInstanceOf(Place::class, $review->reviewable);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_can_be_for_checkpoint(): void
    {
        $trip = Trip::factory()->create();
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
        ]);
        $review = Review::factory()->create([
            'reviewable_type' => MapCheckpoint::class,
            'reviewable_id' => $checkpoint->id,
        ]);

        $this->assertEquals(MapCheckpoint::class, $review->reviewable_type);
        $this->assertEquals($checkpoint->id, $review->reviewable_id);
        $this->assertInstanceOf(MapCheckpoint::class, $review->reviewable);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_have_multiple_reviews(): void
    {
        $user = User::factory()->create();
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place1->id,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place2->id,
        ]);

        $this->assertCount(2, $user->reviews);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function place_can_have_multiple_reviews(): void
    {
        $place = Place::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user1->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);
        Review::factory()->create([
            'user_id' => $user2->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $this->assertCount(2, $place->reviews);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function checkpoint_can_have_multiple_reviews(): void
    {
        $trip = Trip::factory()->create();
        $checkpoint = MapCheckpoint::factory()->create(['trip_id' => $trip->id]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user1->id,
            'reviewable_type' => MapCheckpoint::class,
            'reviewable_id' => $checkpoint->id,
        ]);
        Review::factory()->create([
            'user_id' => $user2->id,
            'reviewable_type' => MapCheckpoint::class,
            'reviewable_id' => $checkpoint->id,
        ]);

        $this->assertCount(2, $checkpoint->reviews);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        // Valid ratings
        $validRatings = [1, 2, 3, 4, 5];
        foreach ($validRatings as $rating) {
            $review = Review::factory()->create([
                'user_id' => $user->id,
                'reviewable_type' => Place::class,
                'reviewable_id' => $place->id,
                'rating' => $rating,
            ]);
            $this->assertEquals($rating, $review->rating);
        }

        $this->assertCount(5, Review::all());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function comment_is_optional(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
            'comment' => null,
        ]);

        $this->assertNull($review->comment);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'comment' => null,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reviews_are_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $reviewId = $review->id;
        $user->delete();

        $this->assertDatabaseMissing('reviews', ['id' => $reviewId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reviews_are_deleted_when_place_is_deleted(): void
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $reviewId = $review->id;
        $place->delete();

        $this->assertDatabaseMissing('reviews', ['id' => $reviewId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_can_scope_by_rating(): void
    {
        $place = Place::factory()->create();
        Review::factory()->count(3)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
        ]);
        Review::factory()->count(2)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 3,
        ]);

        $fiveStarReviews = Review::rating(5)->get();
        $threeStarReviews = Review::rating(3)->get();

        $this->assertCount(3, $fiveStarReviews);
        $this->assertCount(2, $threeStarReviews);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_can_scope_by_user(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        Review::factory()->count(3)->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);
        Review::factory()->count(2)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $userReviews = Review::byUser($user->id)->get();

        $this->assertCount(3, $userReviews);
        $userReviews->each(function ($review) use ($user) {
            $this->assertEquals($user->id, $review->user_id);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_can_get_average_rating_for_reviewable(): void
    {
        $place = Place::factory()->create();

        Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
        ]);
        Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 3,
        ]);
        Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 4,
        ]);

        // Average: (5 + 3 + 4) / 3 = 4.0
        $average = $place->reviews()->avg('rating');

        $this->assertEquals(4.0, $average);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function review_timestamps_are_set_automatically(): void
    {
        $review = Review::factory()->create();

        $this->assertNotNull($review->created_at);
        $this->assertNotNull($review->updated_at);
    }
}
