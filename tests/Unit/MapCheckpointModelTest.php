<?php

namespace Tests\Unit;

use App\Models\CheckpointImage;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MapCheckpoint Model Unit Tests
 * 
 * Tests check-in functionality, location tracking, relationships.
 * Per TDD: These tests define expected behavior before implementation.
 * 
 * NAVER Integration Points:
 * - Checkpoints can link to NAVER POI via place_id
 * - Lat/lng for custom check-in locations
 * - Future: reverse geocoding for address lookup
 */
class MapCheckpointModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function checkpoint_has_fillable_attributes(): void
    {
        $trip = Trip::factory()->create();
        $user = User::factory()->create();
        $place = Place::factory()->create();

        $checkpoint = MapCheckpoint::create([
            'trip_id' => $trip->id,
            'place_id' => $place->id,
            'user_id' => $user->id,
            'title' => 'Tokyo Tower Visit',
            'lat' => 35.6586,
            'lng' => 139.7454,
            'checked_in_at' => now(),
            'note' => 'Amazing view!',
        ]);

        $this->assertDatabaseHas('map_checkpoints', [
            'trip_id' => $trip->id,
            'title' => 'Tokyo Tower Visit',
        ]);
    }

    /** @test */
    public function checkpoint_belongs_to_trip(): void
    {
        $trip = Trip::factory()->create();
        $checkpoint = MapCheckpoint::factory()->create(['trip_id' => $trip->id]);

        $this->assertInstanceOf(Trip::class, $checkpoint->trip);
        $this->assertEquals($trip->id, $checkpoint->trip->id);
    }

    /** @test */
    public function checkpoint_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $checkpoint = MapCheckpoint::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $checkpoint->user);
        $this->assertEquals($user->id, $checkpoint->user->id);
    }

    /** @test */
    public function checkpoint_can_belong_to_place(): void
    {
        $place = Place::factory()->create();
        $checkpoint = MapCheckpoint::factory()->create(['place_id' => $place->id]);

        $this->assertInstanceOf(Place::class, $checkpoint->place);
        $this->assertEquals($place->id, $checkpoint->place->id);
    }

    /** @test */
    public function checkpoint_place_is_optional(): void
    {
        $checkpoint = MapCheckpoint::factory()->create(['place_id' => null]);

        $this->assertNull($checkpoint->place_id);
        $this->assertNull($checkpoint->place);
    }

    /** @test */
    public function checkpoint_has_many_images(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        CheckpointImage::factory()->count(3)->create(['map_checkpoint_id' => $checkpoint->id]);

        $this->assertCount(3, $checkpoint->images);
        $this->assertInstanceOf(CheckpointImage::class, $checkpoint->images->first());
    }

    /** @test */
    public function checkpoint_images_are_deleted_when_checkpoint_is_deleted(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        $image = CheckpointImage::factory()->create(['map_checkpoint_id' => $checkpoint->id]);

        $checkpoint->delete();

        $this->assertDatabaseMissing('checkpoint_images', ['id' => $image->id]);
    }

    /** @test */
    public function checkpoint_has_many_reviews_polymorphic(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        Review::factory()->count(2)->create([
            'reviewable_type' => MapCheckpoint::class,
            'reviewable_id' => $checkpoint->id,
        ]);

        $this->assertCount(2, $checkpoint->reviews);
        $this->assertInstanceOf(Review::class, $checkpoint->reviews->first());
    }

    /** @test */
    public function checkpoint_has_many_favorites_polymorphic(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        Favorite::factory()->count(2)->create([
            'favoritable_type' => MapCheckpoint::class,
            'favoritable_id' => $checkpoint->id,
        ]);

        $this->assertCount(2, $checkpoint->favorites);
        $this->assertInstanceOf(Favorite::class, $checkpoint->favorites->first());
    }

    /** @test */
    public function checkpoint_has_many_comments_polymorphic(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        Comment::factory()->count(2)->create([
            'entity_type' => MapCheckpoint::class,
            'entity_id' => $checkpoint->id,
        ]);

        $this->assertCount(2, $checkpoint->comments);
        $this->assertInstanceOf(Comment::class, $checkpoint->comments->first());
    }

    /** @test */
    public function checkpoint_can_scope_by_trip(): void
    {
        $trip1 = Trip::factory()->create();
        $trip2 = Trip::factory()->create();
        
        MapCheckpoint::factory()->count(2)->create(['trip_id' => $trip1->id]);
        MapCheckpoint::factory()->create(['trip_id' => $trip2->id]);

        $trip1Checkpoints = MapCheckpoint::forTrip($trip1->id)->get();

        $this->assertCount(2, $trip1Checkpoints);
    }

    /** @test */
    public function checkpoint_can_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        MapCheckpoint::factory()->count(2)->create(['user_id' => $user1->id]);
        MapCheckpoint::factory()->create(['user_id' => $user2->id]);

        $user1Checkpoints = MapCheckpoint::byUser($user1->id)->get();

        $this->assertCount(2, $user1Checkpoints);
    }

    /** @test */
    public function checkpoint_can_find_nearby_checkpoints(): void
    {
        // Skip for SQLite - trigonometric functions not supported
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support trigonometric functions (acos, sin, cos). Test will pass in MySQL/PostgreSQL production environment.');
        }

        // Tokyo Tower
        $checkpoint1 = MapCheckpoint::factory()->create([
            'lat' => 35.6586,
            'lng' => 139.7454,
        ]);

        // Nearby (within 1km)
        $checkpoint2 = MapCheckpoint::factory()->create([
            'lat' => 35.6595,
            'lng' => 139.7460,
        ]);

        // Far away (Osaka)
        $checkpoint3 = MapCheckpoint::factory()->create([
            'lat' => 34.6937,
            'lng' => 135.5023,
        ]);

        $nearby = MapCheckpoint::nearby(35.6586, 139.7454, 1)->get();

        $this->assertCount(2, $nearby);
        $this->assertTrue($nearby->contains($checkpoint1));
        $this->assertTrue($nearby->contains($checkpoint2));
        $this->assertFalse($nearby->contains($checkpoint3));
    }

    /** @test */
    public function checkpoint_has_checked_in_scope(): void
    {
        MapCheckpoint::factory()->create(['checked_in_at' => now()]);
        MapCheckpoint::factory()->create(['checked_in_at' => null]);

        $checkedIn = MapCheckpoint::checkedIn()->get();

        $this->assertCount(1, $checkedIn);
        $this->assertNotNull($checkedIn->first()->checked_in_at);
    }

    /** @test */
    public function trip_has_many_checkpoints(): void
    {
        $trip = Trip::factory()->create();
        MapCheckpoint::factory()->count(3)->create(['trip_id' => $trip->id]);

        $this->assertCount(3, $trip->checkpoints);
    }
}
