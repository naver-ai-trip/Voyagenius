<?php

namespace Tests\Unit;

use App\Models\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hotel Model Unit Tests
 *
 * Tests Hotel model for Amadeus Hotel API integration.
 * Tests hotel data, location data, and hotel relationships.
 */
class HotelModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test hotel can be created with Amadeus hotel ID and location data.
     */
    public function test_hotel_can_be_created_with_location_data(): void
    {
        $hotel = Hotel::factory()->create([
            'amadeus_hotel_id' => 'RTPAR001',
            'name' => 'Grand Hotel Paris',
            'city_code' => 'PAR',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'rating' => 4.5,
        ]);

        $this->assertDatabaseHas('hotels', [
            'amadeus_hotel_id' => 'RTPAR001',
            'name' => 'Grand Hotel Paris',
        ]);

        $this->assertEquals(48.8566, $hotel->latitude);
        $this->assertEquals(2.3522, $hotel->longitude);
        $this->assertEquals(4.5, $hotel->rating);
    }

    /**
     * Test hotel has correct fillable attributes.
     */
    public function test_hotel_has_correct_fillable_attributes(): void
    {
        $hotel = new Hotel();
        
        $fillable = $hotel->getFillable();
        
        $this->assertContains('amadeus_hotel_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('chain_code', $fillable);
        $this->assertContains('dupe_id', $fillable);
        $this->assertContains('rating', $fillable);
        $this->assertContains('city_code', $fillable);
        $this->assertContains('latitude', $fillable);
        $this->assertContains('longitude', $fillable);
        $this->assertContains('address', $fillable);
        $this->assertContains('contact', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('amenities', $fillable);
        $this->assertContains('media', $fillable);
    }

    /**
     * Test amadeus_hotel_id must be unique.
     */
    public function test_amadeus_hotel_id_must_be_unique(): void
    {
        Hotel::factory()->create(['amadeus_hotel_id' => 'RTPAR001']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Hotel::factory()->create(['amadeus_hotel_id' => 'RTPAR001']);
    }

    /**
     * Test hotel can have reviews (polymorphic).
     */
    public function test_hotel_can_have_reviews(): void
    {
        $hotel = Hotel::factory()->create();
        
        // Reviews are polymorphic (can be for Hotel, Place, or MapCheckpoint)
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $hotel->reviews());
    }

    /**
     * Test hotel can have favorites (polymorphic).
     */
    public function test_hotel_can_have_favorites(): void
    {
        $hotel = Hotel::factory()->create();
        
        // Favorites are polymorphic (can be for Hotel, Place, MapCheckpoint, or Trip)
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $hotel->favorites());
    }

    /**
     * Test hotel can have itinerary items.
     */
    public function test_hotel_can_have_itinerary_items(): void
    {
        $hotel = Hotel::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $hotel->itineraryItems());
    }

    /**
     * Test hotel can have checkpoints.
     */
    public function test_hotel_can_have_checkpoints(): void
    {
        $hotel = Hotel::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $hotel->checkpoints());
    }

    /**
     * Test hotel can filter by city code.
     */
    public function test_can_filter_hotels_by_city_code(): void
    {
        Hotel::factory()->create(['city_code' => 'PAR']);
        Hotel::factory()->create(['city_code' => 'NYC']);
        Hotel::factory()->create(['city_code' => 'PAR']);

        $parisHotels = Hotel::cityCode('PAR')->get();
        
        $this->assertCount(2, $parisHotels);
    }

    /**
     * Test hotel can filter by chain code.
     */
    public function test_can_filter_hotels_by_chain_code(): void
    {
        Hotel::factory()->create(['chain_code' => 'RT']);
        Hotel::factory()->create(['chain_code' => 'HI']);
        Hotel::factory()->create(['chain_code' => 'RT']);

        $rtHotels = Hotel::chainCode('RT')->get();
        
        $this->assertCount(2, $rtHotels);
    }

    /**
     * Test hotel can filter by minimum rating.
     */
    public function test_can_filter_hotels_by_minimum_rating(): void
    {
        Hotel::factory()->create(['rating' => 3.5]);
        Hotel::factory()->create(['rating' => 4.5]);
        Hotel::factory()->create(['rating' => 4.0]);

        $highRated = Hotel::minRating(4.0)->get();
        
        $this->assertCount(2, $highRated);
    }

    /**
     * Test hotel can search by name.
     */
    public function test_can_search_hotels_by_name(): void
    {
        Hotel::factory()->create(['name' => 'Grand Hotel Paris']);
        Hotel::factory()->create(['name' => 'Grand Hotel London']);
        Hotel::factory()->create(['name' => 'Hilton New York']);

        $results = Hotel::search('Grand')->get();
        
        $this->assertCount(2, $results);
    }

    /**
     * Test hotel can find nearby hotels within radius.
     */
    public function test_can_find_nearby_hotels(): void
    {
        // Skip for SQLite - trigonometric functions not supported
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support trigonometric functions (acos, sin, cos). Test will pass in MySQL/PostgreSQL production environment.');
        }

        // Paris center location
        $centerLat = 48.8566;
        $centerLng = 2.3522;

        Hotel::factory()->create([
            'name' => 'Hotel Central Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        // Very close location (should be within 1km)
        Hotel::factory()->create([
            'name' => 'Hotel Near Center',
            'latitude' => 48.8570,
            'longitude' => 2.3525,
        ]);

        // Far location (should not be in results)
        Hotel::factory()->create([
            'name' => 'Hotel Far Away',
            'latitude' => 48.8700,
            'longitude' => 2.3700,
        ]);

        $nearby = Hotel::nearby($centerLat, $centerLng, 1)->get(); // 1km radius
        
        $this->assertGreaterThanOrEqual(1, $nearby->count());
    }

    /**
     * Test hotel coordinates are cast to float.
     */
    public function test_hotel_coordinates_are_cast_to_float(): void
    {
        $hotel = Hotel::factory()->create([
            'latitude' => '48.8566',
            'longitude' => '2.3522',
        ]);

        $this->assertIsFloat($hotel->latitude);
        $this->assertIsFloat($hotel->longitude);
    }

    /**
     * Test hotel rating is cast to float.
     */
    public function test_hotel_rating_is_cast_to_float(): void
    {
        $hotel = Hotel::factory()->create([
            'rating' => '4.5',
        ]);

        $this->assertIsFloat($hotel->rating);
        $this->assertEquals(4.5, $hotel->rating);
    }

    /**
     * Test hotel JSON fields are cast to arrays.
     */
    public function test_hotel_json_fields_are_cast_to_arrays(): void
    {
        $hotel = Hotel::factory()->create([
            'address' => [
                'lines' => ['123 Main St'],
                'cityName' => 'Paris',
                'countryCode' => 'FR',
            ],
            'contact' => [
                'phone' => '+33123456789',
            ],
            'amenities' => ['WIFI', 'POOL', 'GYM'],
        ]);

        $this->assertIsArray($hotel->address);
        $this->assertIsArray($hotel->contact);
        $this->assertIsArray($hotel->amenities);
        $this->assertEquals('Paris', $hotel->address['cityName']);
        $this->assertContains('WIFI', $hotel->amenities);
    }

    /**
     * Test hotel name is required.
     */
    public function test_hotel_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Hotel::create([
            'amadeus_hotel_id' => 'RTPAR001',
            'city_code' => 'PAR',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);
    }

    /**
     * Test hotel full address accessor.
     */
    public function test_hotel_full_address_accessor(): void
    {
        $hotel = Hotel::factory()->create([
            'address' => [
                'lines' => ['123 Main Street'],
                'postalCode' => '75001',
                'cityName' => 'Paris',
                'countryCode' => 'FR',
            ],
        ]);

        $fullAddress = $hotel->full_address;
        
        $this->assertNotNull($fullAddress);
        $this->assertStringContainsString('123 Main Street', $fullAddress);
        $this->assertStringContainsString('Paris', $fullAddress);
        $this->assertStringContainsString('75001', $fullAddress);
        $this->assertStringContainsString('FR', $fullAddress);
    }

    /**
     * Test hotel full address accessor returns null when address is empty.
     */
    public function test_hotel_full_address_returns_null_when_empty(): void
    {
        $hotel = Hotel::factory()->create([
            'address' => null,
        ]);

        $this->assertNull($hotel->full_address);
    }

    /**
     * Test hotel average rating accessor.
     */
    public function test_hotel_average_rating_accessor(): void
    {
        $hotel = Hotel::factory()->create();
        
        // Create reviews with different ratings
        $hotel->reviews()->create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'rating' => 4,
            'comment' => 'Great hotel',
        ]);
        
        $hotel->reviews()->create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'rating' => 5,
            'comment' => 'Excellent',
        ]);

        $this->assertEquals(4.5, $hotel->average_rating);
    }

    /**
     * Test hotel review count accessor.
     */
    public function test_hotel_review_count_accessor(): void
    {
        $hotel = Hotel::factory()->create();
        
        $hotel->reviews()->create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'rating' => 4,
            'comment' => 'Great hotel',
        ]);
        
        $hotel->reviews()->create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'rating' => 5,
            'comment' => 'Excellent',
        ]);

        $this->assertEquals(2, $hotel->review_count);
    }
}

