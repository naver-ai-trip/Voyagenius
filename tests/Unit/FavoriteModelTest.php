<?php

namespace Tests\Unit;

use App\Models\Favorite;
use App\Models\Place;
use App\Models\MapCheckpoint;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

/**
 * TDD Test Suite for Favorite Model
 * 
 * Requirements (ERD-based):
 * - Polymorphic favoritable (places, checkpoints, trips)
 * - User can only favorite an entity once (unique constraint)
 * - Cascade delete when user deleted
 * - Scopes: byUser(), forType(), recent()
 * - Toggle functionality support
 * - Timestamps tracking
 * 
 * NAVER Integration:
 * - Ready for favorites sync/export via NAVER API
 * - Can be used with NAVER Maps for "favorite places" feature
 */
class FavoriteModelTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_be_created_with_required_attributes(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_has_correct_fillable_attributes(): void
    {
        $favorite = new Favorite();
        
        $this->assertEquals(
            ['user_id', 'favoritable_type', 'favoritable_id'],
            $favorite->getFillable()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();
        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $this->assertInstanceOf(User::class, $favorite->user);
        $this->assertEquals($user->id, $favorite->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_has_polymorphic_favoritable_relationship(): void
    {
        $place = Place::factory()->create();
        $favorite = Favorite::factory()->create([
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $this->assertInstanceOf(Place::class, $favorite->favoritable);
        $this->assertEquals($place->id, $favorite->favoritable->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_be_for_place(): void
    {
        $place = Place::factory()->create();
        $favorite = Favorite::factory()->forFavoritable(Place::class, $place->id)->create();

        $this->assertInstanceOf(Place::class, $favorite->favoritable);
        $this->assertEquals($place->id, $favorite->favoritable_id);
        $this->assertEquals(Place::class, $favorite->favoritable_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_be_for_checkpoint(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        $favorite = Favorite::factory()->forFavoritable(MapCheckpoint::class, $checkpoint->id)->create();

        $this->assertInstanceOf(MapCheckpoint::class, $favorite->favoritable);
        $this->assertEquals($checkpoint->id, $favorite->favoritable_id);
        $this->assertEquals(MapCheckpoint::class, $favorite->favoritable_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_be_for_trip(): void
    {
        $trip = Trip::factory()->create();
        $favorite = Favorite::factory()->forFavoritable(Trip::class, $trip->id)->create();

        $this->assertInstanceOf(Trip::class, $favorite->favoritable);
        $this->assertEquals($trip->id, $favorite->favoritable_id);
        $this->assertEquals(Trip::class, $favorite->favoritable_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_have_multiple_favorites(): void
    {
        $user = User::factory()->create();
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place1->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place2->id,
        ]);

        $this->assertCount(2, $user->favorites);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function place_can_have_multiple_favorites_from_different_users(): void
    {
        $place = Place::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user1->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user2->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $this->assertCount(2, $place->favorites);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_favorite_same_entity_twice(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $this->expectException(QueryException::class);

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorites_are_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();
        
        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $favoriteId = $favorite->id;
        $user->delete();

        $this->assertDatabaseMissing('favorites', ['id' => $favoriteId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorites_are_deleted_when_place_is_deleted(): void
    {
        $place = Place::factory()->create();
        
        $favorite = Favorite::factory()->create([
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $favoriteId = $favorite->id;
        $place->delete();

        $this->assertDatabaseMissing('favorites', ['id' => $favoriteId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $place = Place::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user1->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user2->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $user1Favorites = Favorite::byUser($user1->id)->get();

        $this->assertCount(1, $user1Favorites);
        $this->assertEquals($user1->id, $user1Favorites->first()->user_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_scope_by_type(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();
        $checkpoint = MapCheckpoint::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => MapCheckpoint::class,
            'favoritable_id' => $checkpoint->id,
        ]);

        $placeFavorites = Favorite::forType(Place::class)->get();

        $this->assertCount(1, $placeFavorites);
        $this->assertEquals(Place::class, $placeFavorites->first()->favoritable_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_can_get_recent_favorites(): void
    {
        $user = User::factory()->create();
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();

        $old = Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place1->id,
            'created_at' => now()->subDays(5),
        ]);
        $recent = Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place2->id,
            'created_at' => now(),
        ]);

        $recentFavorites = Favorite::recent()->get();

        $this->assertEquals($recent->id, $recentFavorites->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_check_if_entity_is_favorited(): void
    {
        $user = User::factory()->create();
        $place = Place::factory()->create();

        // Not favorited yet
        $this->assertFalse(
            Favorite::where('user_id', $user->id)
                ->where('favoritable_type', Place::class)
                ->where('favoritable_id', $place->id)
                ->exists()
        );

        // Create favorite
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        // Now favorited
        $this->assertTrue(
            Favorite::where('user_id', $user->id)
                ->where('favoritable_type', Place::class)
                ->where('favoritable_id', $place->id)
                ->exists()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function favorite_timestamps_are_set_automatically(): void
    {
        $favorite = Favorite::factory()->create();

        $this->assertNotNull($favorite->created_at);
        $this->assertNotNull($favorite->updated_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function checkpoint_can_have_multiple_favorites(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        Favorite::factory()->count(3)->create([
            'favoritable_type' => MapCheckpoint::class,
            'favoritable_id' => $checkpoint->id,
        ]);

        $this->assertCount(3, $checkpoint->favorites);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function trip_can_have_multiple_favorites(): void
    {
        $trip = Trip::factory()->create();
        Favorite::factory()->count(2)->create([
            'favoritable_type' => Trip::class,
            'favoritable_id' => $trip->id,
        ]);

        $this->assertCount(2, $trip->favorites);
    }
}
