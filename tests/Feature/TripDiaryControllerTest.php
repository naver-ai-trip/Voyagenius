<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\TripDiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripDiaryControllerTest extends TestCase
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
    public function user_can_create_diary_entry_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => $trip->id,
                'entry_date' => '2025-11-05',
                'text' => 'Today was an amazing day exploring Seoul!',
                'mood' => 'happy',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'trip_id',
                    'user_id',
                    'entry_date',
                    'text',
                    'mood',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.trip_id', $trip->id)
            ->assertJsonPath('data.entry_date', '2025-11-05')
            ->assertJsonPath('data.mood', 'happy');

        // Verify database entry (entry_date may be stored as datetime in SQLite)
        $this->assertDatabaseCount('trip_diaries', 1);
        
        $diary = TripDiary::first();
        $this->assertEquals($trip->id, $diary->trip_id);
        $this->assertEquals($this->user->id, $diary->user_id);
        $this->assertEquals('2025-11-05', $diary->entry_date->format('Y-m-d'));
        $this->assertEquals('Today was an amazing day exploring Seoul!', $diary->text);
        $this->assertEquals('happy', $diary->mood);
    }

    /** @test */
    public function user_can_list_diary_entries()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripDiary::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/diaries');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'trip_id', 'user_id', 'entry_date', 'text', 'mood'],
                ],
                'links',
                'meta' => ['current_page', 'total', 'per_page'],
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_diary_entry_details()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/diaries/{$diary->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $diary->id)
            ->assertJsonPath('data.trip_id', $trip->id);
    }

    /** @test */
    public function user_can_update_their_diary_entry()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'text' => 'Original text',
            'mood' => 'happy',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/diaries/{$diary->id}", [
                'text' => 'Updated text',
                'mood' => 'excited',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.text', 'Updated text')
            ->assertJsonPath('data.mood', 'excited');

        $this->assertDatabaseHas('trip_diaries', [
            'id' => $diary->id,
            'text' => 'Updated text',
            'mood' => 'excited',
        ]);
    }

    /** @test */
    public function user_can_delete_their_diary_entry()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/diaries/{$diary->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('trip_diaries', [
            'id' => $diary->id,
        ]);
    }

    /** @test */
    public function diary_creation_requires_authentication()
    {
        $trip = Trip::factory()->create();

        $response = $this->postJson('/api/diaries', [
            'trip_id' => $trip->id,
            'entry_date' => '2025-11-05',
            'text' => 'Test',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function diary_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id', 'entry_date']);
    }

    /** @test */
    public function diary_validates_trip_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => 99999,
                'entry_date' => '2025-11-05',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id']);
    }

    /** @test */
    public function diary_validates_entry_date_format()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => $trip->id,
                'entry_date' => 'invalid-date',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entry_date']);
    }

    /** @test */
    public function diary_text_is_optional()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => $trip->id,
                'entry_date' => '2025-11-05',
                'mood' => 'happy',
            ]);

        $response->assertCreated();
    }

    /** @test */
    public function diary_mood_is_optional()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => $trip->id,
                'entry_date' => '2025-11-05',
                'text' => 'Test entry',
            ]);

        $response->assertCreated();
    }

    /** @test */
    public function user_cannot_create_diary_for_other_users_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => $trip->id,
                'entry_date' => '2025-11-05',
                'text' => 'Test',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_other_users_diary_entry()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/diaries/{$diary->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_update_other_users_diary_entry()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/diaries/{$diary->id}", [
                'text' => 'Hacked text',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_other_users_diary_entry()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/diaries/{$diary->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function diary_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/diaries/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function diary_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripDiary::factory()->count(20)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/diaries');

        $response->assertOk()
            ->assertJsonCount(15, 'data'); // Default 15 per page
    }

    /** @test */
    public function diary_list_can_filter_by_trip_id()
    {
        $trip1 = Trip::factory()->create(['user_id' => $this->user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $this->user->id]);

        TripDiary::factory()->count(2)->create([
            'trip_id' => $trip1->id,
            'user_id' => $this->user->id,
        ]);

        TripDiary::factory()->create([
            'trip_id' => $trip2->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/diaries?trip_id={$trip1->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.trip_id', $trip1->id);
    }

    /** @test */
    public function diary_list_can_filter_by_entry_date()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'entry_date' => '2025-11-05',
        ]);

        TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'entry_date' => '2025-11-06',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/diaries?entry_date=2025-11-05');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entry_date', '2025-11-05');
    }

    /** @test */
    public function diary_list_can_filter_by_mood()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'mood' => 'happy',
        ]);

        TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'mood' => 'tired',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/diaries?mood=happy');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mood', 'happy');
    }

    /** @test */
    public function diary_includes_trip_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/diaries/{$diary->id}");

        $response->assertOk()
            ->assertJsonPath('data.trip.id', $trip->id);
    }

    /** @test */
    public function diary_includes_user_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/diaries/{$diary->id}");

        $response->assertOk()
            ->assertJsonPath('data.user.id', $this->user->id);
    }

    /** @test */
    public function cannot_create_duplicate_diary_entry_for_same_trip_date()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'entry_date' => '2025-11-05',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/diaries', [
                'trip_id' => $trip->id,
                'entry_date' => '2025-11-05',
                'text' => 'Another entry',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entry_date']);
    }
}

