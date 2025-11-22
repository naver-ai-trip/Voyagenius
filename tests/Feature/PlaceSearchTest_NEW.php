<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for Place search and nearby functionality using NAVER Maps API.
 *
 * Tests are written FIRST following TDD methodology.
 * NAVER Maps API calls are mocked to avoid real API requests in tests.
 *
 * Note: This test suite has been refactored to remove naver_place_id dependency.
 * Places are now uniquely identified by their latitude/longitude coordinates.
 */
class PlaceSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Enable NAVER Local Search for tests
        config(['services.naver_developers.local_search.enabled' => true]);
        config(['services.naver_developers.local_search.client_id' => 'test_client_id']);
        config(['services.naver_developers.local_search.client_secret' => 'test_client_secret']);
    }

    /** @test */
    public function user_can_search_nearby_places_by_coordinates()
    {
        // Mock NAVER Maps API response
        Http::fake([
            'https://openapi.naver.com/*' => Http::response([
                'items' => [
                    [
                        'title' => '스타벅스 강남점',
                        'category' => '카페',
                        'address' => '서울시 강남구',
                        'roadAddress' => '서울시 강남구 테헤란로 123',
                        'mapx' => '1270276000', // 127.0276 * 10000000
                        'mapy' => '374979000',  // 37.4979 * 10000000
                        'link' => 'https://place.naver.com/place/123'
                    ],
                    [
                        'title' => '투썸플레이스 역삼점',
                        'category' => '카페',
                        'address' => '서울시 강남구',
                        'roadAddress' => '서울시 강남구 테헤란로 456',
                        'mapx' => '1270286000', // 127.0286
                        'mapy' => '374989000',  // 37.4989
                        'link' => 'https://place.naver.com/place/456'
                    ]
                ],
                'total' => 2
            ], 200)
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'radius' => 10000, // 10km
            'query' => '카페'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'category',
                        'address',
                        'road_address',
                        'latitude',
                        'longitude',
                        'naver_link'
                    ]
                ],
                'meta' => [
                    'total',
                    'search_location' => ['latitude', 'longitude'],
                    'radius'
                ]
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', '스타벅스 강남점')
            ->assertJsonPath('data.0.category', '카페');
    }

    /** @test */
    public function user_can_search_places_without_coordinates()
    {
        // Mock NAVER Maps API response
        Http::fake([
            'https://openapi.naver.com/*' => Http::response([
                'items' => [
                    [
                        'title' => '경복궁',
                        'category' => '관광명소',
                        'address' => '서울시 종로구',
                        'roadAddress' => '서울시 종로구 사직로 161',
                        'mapx' => '1269000000',
                        'mapy' => '376000000',
                        'link' => 'https://place.naver.com/place/789'
                    ]
                ],
                'total' => 1
            ], 200)
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search', [
            'query' => '경복궁'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'category',
                        'address',
                        'latitude',
                        'longitude'
                    ]
                ]
            ])
            ->assertJsonPath('data.0.name', '경복궁');
    }

    /** @test */
    public function user_can_save_searched_place_to_database()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places', [
            'name' => 'Tokyo Tower',
            'latitude' => 35.6585805,
            'longitude' => 139.7454329,
            'address' => '4 Chome-2-8 Shibakoen, Minato City, Tokyo',
            'category' => 'Tourism'
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Tokyo Tower')
            ->assertJsonPath('data.lat', 35.6585805)
            ->assertJsonPath('data.lng', 139.7454329);

        $this->assertDatabaseHas('places', [
            'name' => 'Tokyo Tower',
            'lat' => 35.6585805,
            'lng' => 139.7454329,
            'category' => 'Tourism'
        ]);
    }

    /** @test */
    public function saved_place_can_be_retrieved_from_database()
    {
        $place = Place::factory()->create([
            'name' => 'Kyoto Temple',
            'lat' => 35.0116,
            'lng' => 135.7681,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/places/{$place->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Kyoto Temple')
            ->assertJsonPath('data.lat', 35.0116);
    }

    /** @test */
    public function search_requires_authentication()
    {
        $response = $this->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'query' => '카페'
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function search_nearby_validates_required_coordinates()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search-nearby', [
            'query' => '카페'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /** @test */
    public function search_nearby_validates_coordinate_ranges()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search-nearby', [
            'latitude' => 91, // Invalid
            'longitude' => 181, // Invalid
            'query' => '카페'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /** @test */
    public function search_nearby_validates_radius_range()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'radius' => 50000, // Invalid: max 10000
            'query' => '카페'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    /** @test */
    public function search_nearby_uses_default_radius_when_not_provided()
    {
        Http::fake([
            'https://openapi.naver.com/*' => Http::response([
                'items' => [],
                'total' => 0
            ], 200)
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'query' => '카페'
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.radius', 1000); // Default
    }

    /** @test */
    public function search_text_requires_query_parameter()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['query']);
    }

    /** @test */
    public function duplicate_coordinates_returns_existing_place()
    {
        // First save
        $firstResponse = $this->actingAs($this->user, 'sanctum')->postJson('/api/places', [
            'name' => 'Seoul Station',
            'latitude' => 37.5547,
            'longitude' => 126.9707,
            'category' => 'Transportation'
        ]);

        $firstResponse->assertCreated();

        // Try to save with same coordinates but different name
        $secondResponse = $this->actingAs($this->user, 'sanctum')->postJson('/api/places', [
            'name' => 'Seoul Station Copy',
            'latitude' => 37.5547,
            'longitude' => 126.9707,
            'category' => 'Transportation'
        ]);

        $secondResponse->assertOk()
            ->assertJsonPath('message', 'Place already exists');

        // Verify only one place exists with these coordinates
        $this->assertCount(1, Place::where('lat', 37.5547)->where('lng', 126.9707)->get());
    }
}
