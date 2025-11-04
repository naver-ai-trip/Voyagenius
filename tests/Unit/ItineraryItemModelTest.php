<?php

namespace Tests\Unit;

use App\Models\ItineraryItem;
use App\Models\Place;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ItineraryItem Model Unit Tests
 * 
 * Tests day planning, time slots, place linking, and trip relationships.
 * Per TDD: These tests define expected behavior before implementation.
 */
class ItineraryItemModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function itinerary_item_has_fillable_attributes(): void
    {
        $trip = Trip::factory()->create();
        $place = Place::factory()->create();

        $item = ItineraryItem::create([
            'trip_id' => $trip->id,
            'title' => 'Visit Tokyo Tower',
            'day_number' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'place_id' => $place->id,
            'note' => 'Bring camera for photos',
        ]);

        $this->assertDatabaseHas('itinerary_items', [
            'trip_id' => $trip->id,
            'title' => 'Visit Tokyo Tower',
            'day_number' => 1,
        ]);
    }

    /** @test */
    public function itinerary_item_belongs_to_trip(): void
    {
        $trip = Trip::factory()->create();
        $item = ItineraryItem::factory()->create(['trip_id' => $trip->id]);

        $this->assertInstanceOf(Trip::class, $item->trip);
        $this->assertEquals($trip->id, $item->trip->id);
    }

    /** @test */
    public function itinerary_item_can_belong_to_place(): void
    {
        $place = Place::factory()->create();
        $item = ItineraryItem::factory()->create(['place_id' => $place->id]);

        $this->assertInstanceOf(Place::class, $item->place);
        $this->assertEquals($place->id, $item->place->id);
    }

    /** @test */
    public function itinerary_item_place_is_optional(): void
    {
        $item = ItineraryItem::factory()->create(['place_id' => null]);

        $this->assertNull($item->place_id);
        $this->assertNull($item->place);
    }

    /** @test */
    public function trip_has_many_itinerary_items(): void
    {
        $trip = Trip::factory()->create();
        ItineraryItem::factory()->count(3)->create(['trip_id' => $trip->id]);

        $this->assertCount(3, $trip->itineraryItems);
        $this->assertInstanceOf(ItineraryItem::class, $trip->itineraryItems->first());
    }

    /** @test */
    public function itinerary_items_are_deleted_when_trip_is_deleted(): void
    {
        $trip = Trip::factory()->create();
        $item = ItineraryItem::factory()->create(['trip_id' => $trip->id]);

        $trip->delete();

        $this->assertDatabaseMissing('itinerary_items', ['id' => $item->id]);
    }

    /** @test */
    public function itinerary_item_can_have_day_scope_filter(): void
    {
        $trip = Trip::factory()->create();
        ItineraryItem::factory()->create(['trip_id' => $trip->id, 'day_number' => 1]);
        ItineraryItem::factory()->create(['trip_id' => $trip->id, 'day_number' => 1]);
        ItineraryItem::factory()->create(['trip_id' => $trip->id, 'day_number' => 2]);

        $dayOneItems = ItineraryItem::forDay(1)->get();

        $this->assertCount(2, $dayOneItems);
        $this->assertTrue($dayOneItems->every(fn($item) => $item->day_number === 1));
    }

    /** @test */
    public function itinerary_item_can_scope_by_trip(): void
    {
        $trip1 = Trip::factory()->create();
        $trip2 = Trip::factory()->create();
        
        ItineraryItem::factory()->count(2)->create(['trip_id' => $trip1->id]);
        ItineraryItem::factory()->create(['trip_id' => $trip2->id]);

        $trip1Items = ItineraryItem::forTrip($trip1->id)->get();

        $this->assertCount(2, $trip1Items);
        $this->assertTrue($trip1Items->every(fn($item) => $item->trip_id === $trip1->id));
    }

    /** @test */
    public function itinerary_item_can_be_ordered_by_day_and_time(): void
    {
        $trip = Trip::factory()->create();
        
        $item1 = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'day_number' => 2,
            'start_time' => '09:00:00',
        ]);
        
        $item2 = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'day_number' => 1,
            'start_time' => '14:00:00',
        ]);
        
        $item3 = ItineraryItem::factory()->create([
            'trip_id' => $trip->id,
            'day_number' => 1,
            'start_time' => '09:00:00',
        ]);

        $ordered = ItineraryItem::ordered()->get();

        $this->assertEquals($item3->id, $ordered[0]->id);
        $this->assertEquals($item2->id, $ordered[1]->id);
        $this->assertEquals($item1->id, $ordered[2]->id);
    }

    /** @test */
    public function itinerary_item_has_duration_accessor(): void
    {
        $item = ItineraryItem::factory()->create([
            'start_time' => '09:00:00',
            'end_time' => '12:30:00',
        ]);

        $this->assertEquals(210, $item->duration_minutes); // 3.5 hours = 210 minutes
    }

    /** @test */
    public function itinerary_item_duration_is_null_when_times_missing(): void
    {
        $item = ItineraryItem::factory()->create([
            'start_time' => null,
            'end_time' => null,
        ]);

        $this->assertNull($item->duration_minutes);
    }

    /** @test */
    public function itinerary_item_allows_day_number_to_be_set(): void
    {
        // Note: Database-level validation for positive integers requires CHECK constraints
        // which are not supported in all databases. Validation should be done at the
        // application level via FormRequest validation rules.
        
        $item = ItineraryItem::factory()->create(['day_number' => 5]);
        
        $this->assertEquals(5, $item->day_number);
        $this->assertIsInt($item->day_number);
    }
}
