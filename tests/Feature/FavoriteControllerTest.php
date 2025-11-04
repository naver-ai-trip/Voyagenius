<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function user_can_favorite_a_place()
    {
        $place = Place::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'place',
                'favoritable_id' => $place->id,
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'user_id' => $this->user->id,
                    'favoritable_type' => 'place',
                    'favoritable_id' => $place->id,
                ],
            ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);
    }

    /** @test */
    public function user_can_favorite_a_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'trip',
                'favoritable_id' => $trip->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'favoritable_type' => Trip::class,
            'favoritable_id' => $trip->id,
        ]);
    }

    /** @test */
    public function user_can_favorite_a_checkpoint()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'map_checkpoint',
                'favoritable_id' => $checkpoint->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'favoritable_type' => MapCheckpoint::class,
            'favoritable_id' => $checkpoint->id,
        ]);
    }

    /** @test */
    public function user_can_list_their_favorites()
    {
        $place = Place::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Trip::class,
            'favoritable_id' => $trip->id,
        ]);

        // Other user's favorite (should not appear)
        Favorite::factory()->create([
            'user_id' => $this->otherUser->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/favorites');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_unfavorite_an_entity()
    {
        $place = Place::factory()->create();
        $favorite = Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/favorites/{$favorite->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
        ]);
    }

    /** @test */
    public function favorite_creation_requires_authentication()
    {
        $place = Place::factory()->create();

        $response = $this->postJson('/api/favorites', [
            'favoritable_type' => 'place',
            'favoritable_id' => $place->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function favorite_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favoritable_type', 'favoritable_id']);
    }

    /** @test */
    public function favorite_validates_favoritable_type_enum()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'invalid_type',
                'favoritable_id' => 1,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favoritable_type']);
    }

    /** @test */
    public function favorite_validates_place_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'place',
                'favoritable_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favoritable_id']);
    }

    /** @test */
    public function favorite_validates_trip_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'trip',
                'favoritable_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favoritable_id']);
    }

    /** @test */
    public function favorite_validates_checkpoint_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'map_checkpoint',
                'favoritable_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favoritable_id']);
    }

    /** @test */
    public function user_cannot_favorite_same_entity_twice()
    {
        $place = Place::factory()->create();

        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/favorites', [
                'favoritable_type' => 'place',
                'favoritable_id' => $place->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favoritable_id']);
    }

    /** @test */
    public function user_cannot_delete_other_users_favorite()
    {
        $place = Place::factory()->create();
        $favorite = Favorite::factory()->create([
            'user_id' => $this->otherUser->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/favorites/{$favorite->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function favorite_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/favorites/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function favorite_list_is_paginated()
    {
        // Create 20 favorites
        for ($i = 0; $i < 20; $i++) {
            $place = Place::factory()->create();
            Favorite::factory()->create([
                'user_id' => $this->user->id,
                'favoritable_type' => Place::class,
                'favoritable_id' => $place->id,
            ]);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/favorites');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'total', 'per_page'],
            ])
            ->assertJsonCount(15, 'data'); // Default 15 per page
    }

    /** @test */
    public function favorite_list_can_filter_by_favoritable_type()
    {
        $place = Place::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Trip::class,
            'favoritable_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/favorites?favoritable_type=place');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.favoritable_type', 'place');
    }

    /** @test */
    public function favorite_includes_favoritable_data_when_loaded()
    {
        $place = Place::factory()->create(['name' => 'Test Place']);
        Favorite::factory()->create([
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/favorites');

        $response->assertOk()
            ->assertJsonPath('data.0.favoritable.name', 'Test Place');
    }
}
