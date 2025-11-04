<?php

namespace Tests\Feature;

use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for MapCheckpointController API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, store, show, update, destroy)
 * - Validation (required fields, lat/lng ranges, trip ownership)
 * - Authorization (only trip owner/editors can modify)
 * - Relationships (place, trip loaded)
 * - Business logic (check-in, filtering)
 * - Status codes and JSON structure
 */
class MapCheckpointControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function user_can_list_checkpoints_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        // Create checkpoints for user's trip
        MapCheckpoint::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        // Create checkpoints for other trip (should not appear)
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        MapCheckpoint::factory()->count(2)->create([
            'trip_id' => $otherTrip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints?trip_id={$trip->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'trip_id',
                        'user_id',
                        'place_id',
                        'title',
                        'lat',
                        'lng',
                        'checked_in_at',
                        'note',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_can_create_checkpoint_with_valid_data()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Tokyo Tower',
            'lat' => 35.6586,
            'lng' => 139.7454,
            'note' => 'Amazing view from the top!',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Tokyo Tower')
            ->assertJsonPath('data.trip_id', $trip->id)
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.lat', 35.6586)
            ->assertJsonPath('data.lng', 139.7454);

        $this->assertDatabaseHas('map_checkpoints', [
            'title' => 'Tokyo Tower',
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'lat' => 35.6586,
            'lng' => 139.7454,
        ]);
    }

    /** @test */
    public function user_can_create_checkpoint_with_place_id()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $place = Place::factory()->create();

        $checkpointData = [
            'trip_id' => $trip->id,
            'place_id' => $place->id,
            'title' => 'Senso-ji Temple',
            'lat' => 35.7148,
            'lng' => 139.7967,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertCreated()
            ->assertJsonPath('data.place_id', $place->id);

        $this->assertDatabaseHas('map_checkpoints', [
            'place_id' => $place->id,
            'title' => 'Senso-ji Temple',
        ]);
    }

    /** @test */
    public function user_can_view_checkpoint_details()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'title' => 'Mount Fuji',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$checkpoint->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $checkpoint->id)
            ->assertJsonPath('data.title', 'Mount Fuji');
    }

    /** @test */
    public function user_can_update_their_checkpoint()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'note' => 'Updated note',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/checkpoints/{$checkpoint->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.note', 'Updated note');

        $this->assertDatabaseHas('map_checkpoints', [
            'id' => $checkpoint->id,
            'title' => 'Updated Title',
            'note' => 'Updated note',
        ]);
    }

    /** @test */
    public function user_can_delete_their_checkpoint()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checkpoints/{$checkpoint->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('map_checkpoints', [
            'id' => $checkpoint->id,
        ]);
    }

    /** @test */
    public function checkpoint_creation_requires_authentication()
    {
        $trip = Trip::factory()->create();

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Tokyo Tower',
            'lat' => 35.6586,
            'lng' => 139.7454,
        ];

        $response = $this->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnauthorized();
    }

    /** @test */
    public function checkpoint_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id', 'title', 'lat', 'lng']);
    }

    /** @test */
    public function checkpoint_creation_validates_trip_exists()
    {
        $checkpointData = [
            'trip_id' => 99999, // Non-existent trip
            'title' => 'Tokyo Tower',
            'lat' => 35.6586,
            'lng' => 139.7454,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id']);
    }

    /** @test */
    public function checkpoint_creation_validates_latitude_range()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Invalid Location',
            'lat' => 91, // Exceeds max 90
            'lng' => 139.7454,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    /** @test */
    public function checkpoint_creation_validates_longitude_range()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Invalid Location',
            'lat' => 35.6586,
            'lng' => 181, // Exceeds max 180
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    }

    /** @test */
    public function checkpoint_creation_validates_title_length()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => str_repeat('a', 256), // Exceeds max 255
            'lat' => 35.6586,
            'lng' => 139.7454,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function checkpoint_note_is_optional()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Tokyo Tower',
            'lat' => 35.6586,
            'lng' => 139.7454,
            // No note provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertCreated();
    }

    /** @test */
    public function checkpoint_place_id_is_optional()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Custom Location',
            'lat' => 35.6586,
            'lng' => 139.7454,
            // No place_id provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertCreated()
            ->assertJsonPath('data.place_id', null);
    }

    /** @test */
    public function checkpoint_validates_place_id_exists()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'place_id' => 99999, // Non-existent place
            'title' => 'Invalid Place',
            'lat' => 35.6586,
            'lng' => 139.7454,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['place_id']);
    }

    /** @test */
    public function user_cannot_create_checkpoint_for_other_users_trip()
    {
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $checkpointData = [
            'trip_id' => $otherTrip->id,
            'title' => 'Unauthorized Checkpoint',
            'lat' => 35.6586,
            'lng' => 139.7454,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_checkpoint_from_other_users_trip()
    {
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $otherTrip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$checkpoint->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_update_other_users_checkpoint()
    {
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $otherTrip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $updateData = [
            'title' => 'Hacked Title',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/checkpoints/{$checkpoint->id}", $updateData);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_other_users_checkpoint()
    {
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $otherTrip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checkpoints/{$checkpoint->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function checkpoint_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checkpoints/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function checkpoint_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        MapCheckpoint::factory()->count(20)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints?trip_id={$trip->id}&per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    /** @test */
    public function checkpoint_list_can_filter_by_checked_in_status()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        MapCheckpoint::factory()->count(3)->checkedIn()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        MapCheckpoint::factory()->count(2)->notCheckedIn()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints?trip_id={$trip->id}&checked_in=true");

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        // Verify all returned checkpoints are checked in
        $data = $response->json('data');
        foreach ($data as $checkpoint) {
            $this->assertNotNull($checkpoint['checked_in_at']);
        }
    }

    /** @test */
    public function checkpoint_includes_place_data_when_place_id_provided()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $place = Place::factory()->create(['name' => 'Tokyo Tower']);
        
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'place_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$checkpoint->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'place' => [
                        'id',
                        'name',
                    ]
                ]
            ]);
    }

    /** @test */
    public function checkpoint_checked_in_at_can_be_set_on_creation()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkedInTime = now();

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Tokyo Tower',
            'lat' => 35.6586,
            'lng' => 139.7454,
            'checked_in_at' => $checkedInTime->toIso8601String(),
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertCreated();

        $this->assertDatabaseHas('map_checkpoints', [
            'title' => 'Tokyo Tower',
            'checked_in_at' => $checkedInTime->format('Y-m-d H:i:s'),
        ]);
    }

    /** @test */
    public function checkpoint_checked_in_at_is_nullable()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Future Checkpoint',
            'lat' => 35.6586,
            'lng' => 139.7454,
            // No checked_in_at provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertCreated()
            ->assertJsonPath('data.checked_in_at', null);
    }

    /** @test */
    public function checkpoint_validates_checked_in_at_date_format()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpointData = [
            'trip_id' => $trip->id,
            'title' => 'Tokyo Tower',
            'lat' => 35.6586,
            'lng' => 139.7454,
            'checked_in_at' => 'invalid-date-format',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints', $checkpointData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['checked_in_at']);
    }
}
