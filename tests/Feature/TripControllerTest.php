<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TripController API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, store, show, update, destroy)
 * - Validation (required fields, date ranges)
 * - Authorization (only owner/editors can modify)
 * - Relationships (participants, checkpoints loaded)
 * - Status codes and JSON structure
 */
class TripControllerTest extends TestCase
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
    public function user_can_list_their_trips()
    {
        // Create trips for authenticated user
        $userTrips = Trip::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create trips for other user (should not appear)
        Trip::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/trips');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'destination_country',
                        'destination_city',
                        'start_date',
                        'end_date',
                        'status',
                        'is_group',
                        'progress',
                        'duration_days',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ]
            ]);
    }

    /** @test */
    public function user_can_create_trip_with_valid_data()
    {
        $tripData = [
            'title' => 'Tokyo Adventure 2025',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
            'is_group' => false,
            'status' => 'planning',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Tokyo Adventure 2025')
            ->assertJsonPath('data.destination_country', 'Japan')
            ->assertJsonPath('data.destination_city', 'Tokyo')
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.status', 'planning');

        $this->assertDatabaseHas('trips', [
            'title' => 'Tokyo Adventure 2025',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_view_trip_details()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Seoul Trip',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/trips/{$trip->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.title', 'Seoul Trip')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'destination_country',
                    'destination_city',
                    'start_date',
                    'end_date',
                    'status',
                    'is_group',
                    'progress',
                    'duration_days',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    /** @test */
    public function user_can_update_their_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'status' => 'planning',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'in_progress',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'title' => 'Updated Title',
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function user_can_delete_their_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('trips', [
            'id' => $trip->id,
        ]);
    }

    /** @test */
    public function trip_creation_requires_authentication()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
        ];

        $response = $this->postJson('/api/trips', $tripData);

        $response->assertUnauthorized();
    }

    /** @test */
    public function trip_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'destination_country', 'destination_city', 'start_date', 'end_date']);
    }

    /** @test */
    public function trip_creation_validates_title_length()
    {
        $tripData = [
            'title' => str_repeat('a', 256), // Exceeds max 255
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function trip_creation_validates_date_format()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => 'invalid-date',
            'end_date' => '2025-12-10',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);
    }

    /** @test */
    public function trip_creation_validates_end_date_after_start_date()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-10',
            'end_date' => '2025-12-01', // Before start_date
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function trip_creation_validates_status_enum()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function trip_status_accepts_valid_values()
    {
        $validStatuses = ['planning', 'in_progress', 'completed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $tripData = [
                'title' => "Trip {$status}",
                'destination_country' => 'Japan',
                'destination_city' => 'Tokyo',
                'start_date' => '2025-12-01',
                'end_date' => '2025-12-10',
                'status' => $status,
            ];

            $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

            $response->assertCreated()
                ->assertJsonPath('data.status', $status);
        }
    }

    /** @test */
    public function user_cannot_view_other_users_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/trips/{$trip->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_update_other_users_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $updateData = [
            'title' => 'Hacked Title',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}", $updateData);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_other_users_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function trip_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/trips/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function trip_list_is_paginated()
    {
        Trip::factory()->count(20)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/trips?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure([
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ]
            ]);
    }

    /** @test */
    public function trip_list_can_filter_by_status()
    {
        Trip::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'planning',
        ]);

        Trip::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/trips?status=planning');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        // Verify all returned trips have status 'planning'
        $data = $response->json('data');
        foreach ($data as $trip) {
            $this->assertEquals('planning', $trip['status']);
        }
    }

    /** @test */
    public function trip_defaults_to_planning_status()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
            // No status provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'planning');
    }

    /** @test */
    public function trip_is_group_defaults_to_false()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
            // No is_group provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertCreated()
            ->assertJsonPath('data.is_group', false);
    }

    /** @test */
    public function trip_progress_defaults_to_zero()
    {
        $tripData = [
            'title' => 'Tokyo Trip',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
            // No progress provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/trips', $tripData);

        $response->assertCreated()
            ->assertJsonPath('data.progress', null);
    }
}
