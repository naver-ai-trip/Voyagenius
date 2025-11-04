<?php

namespace Tests\Feature;

use App\Models\ItineraryItem;
use App\Models\Place;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryItemControllerTest extends TestCase
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
    public function user_can_create_itinerary_item_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Visit Tokyo Tower',
            'day_number' => 1,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'note' => 'Buy tickets in advance',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'title' => 'Visit Tokyo Tower',
                    'day_number' => 1,
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'note' => 'Buy tickets in advance',
                ],
            ]);

        $this->assertDatabaseHas('itinerary_items', [
            'trip_id' => $trip->id,
            'title' => 'Visit Tokyo Tower',
            'day_number' => 1,
        ]);
    }

    /** @test */
    public function user_can_create_itinerary_item_with_place()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $place = Place::factory()->create();

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Lunch at Sushi Restaurant',
            'day_number' => 2,
            'start_time' => '12:00:00',
            'end_time' => '13:30:00',
            'place_id' => $place->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'place_id' => $place->id,
                ],
            ]);

        $this->assertDatabaseHas('itinerary_items', [
            'trip_id' => $trip->id,
            'place_id' => $place->id,
        ]);
    }

    /** @test */
    public function user_can_list_itinerary_items()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        
        ItineraryItem::factory()->count(3)->create([
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/itinerary-items');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'day_number', 'start_time', 'end_time'],
                ],
            ]);
    }

    /** @test */
    public function user_can_view_itinerary_item_details()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'title' => 'Museum Visit',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/itinerary-items/{$item->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $item->id,
                    'title' => 'Museum Visit',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_their_itinerary_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'title' => 'Original Title',
        ]);

        $data = [
            'title' => 'Updated Title',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/itinerary-items/{$item->id}", $data);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                    'start_time' => '14:00:00',
                    'end_time' => '16:00:00',
                ],
            ]);

        $this->assertDatabaseHas('itinerary_items', [
            'id' => $item->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function user_can_delete_their_itinerary_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/itinerary-items/{$item->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('itinerary_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function itinerary_item_creation_requires_authentication()
    {
        $trip = Trip::factory()->create();

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Test Item',
            'day_number' => 1,
        ];

        $response = $this->postJson('/api/itinerary-items', $data);

        $response->assertUnauthorized();
    }

    /** @test */
    public function itinerary_item_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id', 'title', 'day_number']);
    }

    /** @test */
    public function itinerary_item_validates_trip_exists()
    {
        $data = [
            'trip_id' => 99999,
            'title' => 'Test Item',
            'day_number' => 1,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id']);
    }

    /** @test */
    public function itinerary_item_validates_place_exists()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Test Item',
            'day_number' => 1,
            'place_id' => 99999,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['place_id']);
    }

    /** @test */
    public function itinerary_item_validates_day_number_minimum()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Test Item',
            'day_number' => 0,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['day_number']);
    }

    /** @test */
    public function itinerary_item_validates_title_length()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => str_repeat('a', 256),
            'day_number' => 1,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function itinerary_item_validates_time_format()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Test Item',
            'day_number' => 1,
            'start_time' => 'invalid-time',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
    }

    /** @test */
    public function itinerary_item_times_are_optional()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Flexible Activity',
            'day_number' => 1,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertCreated();

        $this->assertDatabaseHas('itinerary_items', [
            'title' => 'Flexible Activity',
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /** @test */
    public function itinerary_item_place_is_optional()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'General Activity',
            'day_number' => 1,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertCreated();

        $this->assertDatabaseHas('itinerary_items', [
            'title' => 'General Activity',
            'place_id' => null,
        ]);
    }

    /** @test */
    public function user_cannot_create_itinerary_item_for_other_users_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $data = [
            'trip_id' => $trip->id,
            'title' => 'Test Item',
            'day_number' => 1,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/itinerary-items', $data);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_itinerary_item_from_other_users_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/itinerary-items/{$item->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_update_itinerary_item_from_other_users_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
        ]);

        $data = ['title' => 'Hacked Title'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/itinerary-items/{$item->id}", $data);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_itinerary_item_from_other_users_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/itinerary-items/{$item->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function itinerary_item_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/itinerary-items/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function itinerary_item_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        
        ItineraryItem::factory()->count(20)->create([
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/itinerary-items');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(15, 'data');
    }

    /** @test */
    public function itinerary_item_list_can_filter_by_trip_id()
    {
        $trip1 = Trip::factory()->create(['user_id' => $this->user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $this->user->id]);

        ItineraryItem::factory()->count(3)->create(['trip_id' => $trip1->id]);
        ItineraryItem::factory()->count(2)->create(['trip_id' => $trip2->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/itinerary-items?trip_id={$trip1->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function itinerary_item_list_can_filter_by_day_number()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        ItineraryItem::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'day_number' => 1,
        ]);
        ItineraryItem::factory()->count(2)->create([
            'trip_id' => $trip->id,
            'day_number' => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/itinerary-items?day_number=1');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function itinerary_item_list_is_ordered_by_day_and_time()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $item1 = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'title' => 'Day 2 Morning',
            'day_number' => 2,
            'start_time' => '09:00:00',
        ]);
        $item2 = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'title' => 'Day 1 Morning',
            'day_number' => 1,
            'start_time' => '09:00:00',
        ]);
        $item3 = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'title' => 'Day 1 Afternoon',
            'day_number' => 1,
            'start_time' => '14:00:00',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/itinerary-items');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals('Day 1 Morning', $data[0]['title']);
        $this->assertEquals('Day 1 Afternoon', $data[1]['title']);
        $this->assertEquals('Day 2 Morning', $data[2]['title']);
    }

    /** @test */
    public function itinerary_item_includes_trip_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/itinerary-items/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'trip' => ['id', 'title'],
                ],
            ]);
    }

    /** @test */
    public function itinerary_item_includes_place_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $place = Place::factory()->create();
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'place_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/itinerary-items/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'place' => ['id', 'name'],
                ],
            ]);
    }

    /** @test */
    public function itinerary_item_includes_duration_in_minutes()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'start_time' => '09:00:00',
            'end_time' => '11:30:00',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/itinerary-items/{$item->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'duration_minutes' => 150, // 2.5 hours
                ],
            ]);
    }
}
