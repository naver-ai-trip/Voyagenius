<?php

namespace Tests\Unit;

use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Place Model Unit Tests
 *
 * Tests Place model for NAVER Maps POI integration.
 * Tests geocoding, location data, and place relationships.
 *
 * Note: This test suite has been refactored to remove naver_place_id dependency.
 * Places are now uniquely identified by their latitude/longitude coordinates.
 */
class PlaceModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test place can be created with location data (coordinates are primary identifier).
     */
    public function test_place_can_be_created_with_location_data(): void
    {
        $place = Place::factory()->create([
            'name' => 'Tokyo Tower',
            'address' => '4 Chome-2-8 Shibakoen, Minato City, Tokyo',
            'lat' => 35.6585805,
            'lng' => 139.7454329,
            'category' => 'Tourism',
        ]);

        $this->assertDatabaseHas('places', [
            'name' => 'Tokyo Tower',
        ]);

        $this->assertEquals(35.6585805, $place->lat);
        $this->assertEquals(139.7454329, $place->lng);
    }

    /**
     * Test place has correct fillable attributes.
     */
    public function test_place_has_correct_fillable_attributes(): void
    {
        $place = new Place();
        
        $fillable = $place->getFillable();
        
        $this->assertContains('name', $fillable);
        $this->assertContains('address', $fillable);
        $this->assertContains('lat', $fillable);
        $this->assertContains('lng', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertNotContains('naver_place_id', $fillable);
    }

    /**
     * Test lat/lng combination must be unique.
     */
    public function test_lat_lng_combination_must_be_unique(): void
    {
        Place::factory()->create([
            'lat' => 35.6585805,
            'lng' => 139.7454329
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Place::factory()->create([
            'lat' => 35.6585805,
            'lng' => 139.7454329
        ]);
    }

    /**
     * Test place can have reviews (polymorphic).
     */
    public function test_place_can_have_reviews(): void
    {
        $place = Place::factory()->create();
        
        // Reviews are polymorphic (can be for Place or MapCheckpoint)
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $place->reviews());
    }

    /**
     * Test place can have favorites (polymorphic).
     */
    public function test_place_can_have_favorites(): void
    {
        $place = Place::factory()->create();
        
        // Favorites are polymorphic (can be for Place, MapCheckpoint, or Trip)
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $place->favorites());
    }

    /**
     * Test place can have itinerary items.
     */
    public function test_place_can_have_itinerary_items(): void
    {
        $place = Place::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $place->itineraryItems());
    }

    /**
     * Test place can have checkpoints.
     */
    public function test_place_can_have_checkpoints(): void
    {
        $place = Place::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $place->checkpoints());
    }

    /**
     * Test place can filter by category.
     */
    public function test_can_filter_places_by_category(): void
    {
        Place::factory()->create(['category' => 'Tourism']);
        Place::factory()->create(['category' => 'Restaurant']);
        Place::factory()->create(['category' => 'Tourism']);

        $tourism = Place::category('Tourism')->get();
        
        $this->assertCount(2, $tourism);
    }

    /**
     * Test place can search by name.
     */
    public function test_can_search_places_by_name(): void
    {
        Place::factory()->create(['name' => 'Tokyo Tower']);
        Place::factory()->create(['name' => 'Tokyo Skytree']);
        Place::factory()->create(['name' => 'Osaka Castle']);

        $results = Place::search('Tokyo')->get();
        
        $this->assertCount(2, $results);
    }

    /**
     * Test place can find nearby places within radius.
     */
    public function test_can_find_nearby_places(): void
    {
        // Skip for SQLite - trigonometric functions not supported
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support trigonometric functions (acos, sin, cos). Test will pass in MySQL/PostgreSQL production environment.');
        }

        // Tokyo Tower location
        $centerLat = 35.6585805;
        $centerLng = 139.7454329;

        Place::factory()->create([
            'name' => 'Tokyo Tower',
            'lat' => 35.6585805,
            'lng' => 139.7454329,
        ]);

        // Very close location (should be within 1km)
        Place::factory()->create([
            'name' => 'Zojoji Temple',
            'lat' => 35.6575,
            'lng' => 139.7461,
        ]);

        // Far location (should not be in results)
        Place::factory()->create([
            'name' => 'Tokyo Skytree',
            'lat' => 35.7101,
            'lng' => 139.8107,
        ]);

        $nearby = Place::nearby($centerLat, $centerLng, 1)->get(); // 1km radius
        
        $this->assertGreaterThanOrEqual(1, $nearby->count());
    }

    /**
     * Test place coordinates are cast to float.
     */
    public function test_place_coordinates_are_cast_to_float(): void
    {
        $place = Place::factory()->create([
            'lat' => '35.6585805',
            'lng' => '139.7454329',
        ]);

        $this->assertIsFloat($place->lat);
        $this->assertIsFloat($place->lng);
    }

    /**
     * Test place name is required.
     */
    public function test_place_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Place::create([
            'address' => 'Some address',
            'lat' => 35.6585805,
            'lng' => 139.7454329,
        ]);
    }
}

