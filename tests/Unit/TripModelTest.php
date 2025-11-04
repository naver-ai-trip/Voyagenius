<?php

namespace Tests\Unit;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Trip Model Unit Tests
 *
 * Following TDD - these tests define the Trip model behavior before implementation.
 * Tests cover: attributes, validation, relationships, status transitions, scopes.
 */
class TripModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test trip can be created with required attributes.
     */
    public function test_trip_can_be_created_with_required_attributes(): void
    {
        $user = User::factory()->create();
        
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'title' => 'Tokyo Adventure',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
        ]);

        $this->assertDatabaseHas('trips', [
            'user_id' => $user->id,
            'title' => 'Tokyo Adventure',
            'destination_country' => 'Japan',
            'destination_city' => 'Tokyo',
        ]);

        $this->assertEquals('Tokyo Adventure', $trip->title);
    }

    /**
     * Test trip has correct fillable attributes.
     */
    public function test_trip_has_correct_fillable_attributes(): void
    {
        $trip = new Trip();
        
        $fillable = $trip->getFillable();
        
        $this->assertContains('user_id', $fillable);
        $this->assertContains('title', $fillable);
        $this->assertContains('destination_country', $fillable);
        $this->assertContains('destination_city', $fillable);
        $this->assertContains('start_date', $fillable);
        $this->assertContains('end_date', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('is_group', $fillable);
        $this->assertContains('progress', $fillable);
    }

    /**
     * Test trip belongs to a user.
     */
    public function test_trip_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $trip->user);
        $this->assertEquals($user->id, $trip->user->id);
    }

    /**
     * Test trip can have default status of 'planning'.
     */
    public function test_trip_has_default_status_planning(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertEquals('planning', $trip->status);
    }

    /**
     * Test trip can have different statuses.
     */
    public function test_trip_can_have_different_statuses(): void
    {
        $user = User::factory()->create();
        
        $statuses = ['planning', 'ongoing', 'completed', 'cancelled'];
        
        foreach ($statuses as $status) {
            $trip = Trip::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
            ]);
            
            $this->assertEquals($status, $trip->status);
        }
    }

    /**
     * Test trip can be marked as group trip.
     */
    public function test_trip_can_be_group_trip(): void
    {
        $user = User::factory()->create();
        
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'is_group' => true,
        ]);

        $this->assertTrue($trip->is_group);
    }

    /**
     * Test trip can have progress tracking.
     */
    public function test_trip_can_have_progress(): void
    {
        $user = User::factory()->create();
        
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'progress' => 'itinerary_complete',
        ]);

        $this->assertEquals('itinerary_complete', $trip->progress);
    }

    /**
     * Test trip casts dates correctly.
     */
    public function test_trip_casts_dates_correctly(): void
    {
        $user = User::factory()->create();
        
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $trip->start_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $trip->end_date);
    }

    /**
     * Test trip can calculate duration in days.
     */
    public function test_trip_can_calculate_duration(): void
    {
        $user = User::factory()->create();
        
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
        ]);

        $this->assertEquals(10, $trip->duration_days);
    }

    /**
     * Test user can have multiple trips.
     */
    public function test_user_can_have_multiple_trips(): void
    {
        $user = User::factory()->create();
        
        Trip::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->trips);
    }

    /**
     * Test trip title is required.
     */
    public function test_trip_title_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $user = User::factory()->create();
        
        Trip::create([
            'user_id' => $user->id,
            'destination_country' => 'Japan',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
        ]);
    }

    /**
     * Test trip user_id is required.
     */
    public function test_trip_user_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Trip::create([
            'title' => 'Tokyo Adventure',
            'destination_country' => 'Japan',
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
        ]);
    }
}
