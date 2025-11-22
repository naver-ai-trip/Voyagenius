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
        // Mock NAVER Maps API response - override default
        // Coordinates are in NAVER format (multiplied by 10,000,000)
        // Search location: 37.4979, 127.0276 (Gangnam)
        // Mock places within 1km radius
        Http::fake([
            'https://openapi.naver.com/*' => Http::response([
                'items' => [
                    [
                        'title' => '스타벅스 강남점',
                        'category' => '카페',
                        'address' => '서울시 강남구',
                        'roadAddress' => '서울시 강남구 테헤란로 123',
                        'mapx' => '1270276000', // 127.0276 * 10000000 (exact match)
                        'mapy' => '374979000',  // 37.4979 * 10000000 (exact match)
                        'link' => 'https://place.naver.com/place/123'
                    ],
                    [
                        'title' => '투썸플레이스 역삼점',
                        'category' => '카페',
                        'address' => '서울시 강남구',
                        'roadAddress' => '서울시 강남구 테헤란로 456',
                        'mapx' => '1270286000', // 127.0286 (100m east)
                        'mapy' => '374989000',  // 37.4989 (100m north)
                        'link' => 'https://place.naver.com/place/456'
                    ]
                ],
                'total' => 2
            ], 200)
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'radius' => 10000, // 10km meters - large radius for test
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
        // Mock NAVER Maps API response for text-only search
        $this->mockNaverMapsSearchResponse([
            [
                'title' => '경복궁',
                'category' => '관광명소',
                'address' => '서울시 종로구',
                'roadAddress' => '서울시 종로구 사직로 161',
                'mapx' => '1269000000',
                'mapy' => '376000000',
                'link' => 'https://place.naver.com/place/789'
            ]
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/places/search', [
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
    public function user_can_get_place_details_by_naver_place_id()
    {
        // Mock NAVER Maps API response for place details
        $this->mockNaverPlaceDetailsResponse([
            'id' => '12345',
            'name' => '명동성당',
            'category' => '종교시설',
            'address' => '서울시 중구 명동길 74',
            'roadAddress' => '서울시 중구 명동길 74',
            'latitude' => 37.5633,
            'longitude' => 126.9870,
            'phone' => '02-774-1784',
            'description' => '한국 최초의 벽돌조 성당',
            'businessHours' => '월-토 06:00-20:00',
            'link' => 'https://place.naver.com/place/12345'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/places/naver/12345');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'naver_place_id',
                    'name',
                    'category',
                    'address',
                    'road_address',
                    'latitude',
                    'longitude',
                    'phone',
                    'description',
                    'business_hours',
                    'naver_link'
                ]
            ])
            ->assertJsonPath('data.naver_place_id', '12345')
            ->assertJsonPath('data.name', '명동성당');
    }

    /** @test */
    public function user_can_save_searched_place_to_database()
    {
        $this->mockNaverPlaceDetailsResponse([
            'id' => '99999',
            'name' => '남산타워',
            'category' => '관광명소',
            'address' => '서울시 용산구',
            'roadAddress' => '서울시 용산구 남산공원길 105',
            'latitude' => 37.5512,
            'longitude' => 126.9882
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/places', [
            'naver_place_id' => '99999',
            'fetch_details' => true
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', '남산타워')
            ->assertJsonPath('data.naver_place_id', '99999');

        $this->assertDatabaseHas('places', [
            'naver_place_id' => '99999',
            'name' => '남산타워',
            'category' => '관광명소'
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
            ->assertJsonPath('name', 'Kyoto Temple')
            ->assertJsonPath('lat', 35.0116);
    }

    /** @test */
    public function duplicate_coordinates_returns_existing_place()
    {
        // First save
        $this->actingAs($this->user, 'sanctum')->postJson('/api/places', [
            'name' => 'Osaka Castle',
            'latitude' => 34.6873,
            'longitude' => 135.5262,
            'category' => 'Tourism'
        ]);

        // Try to save again with same coordinates
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/places', [
            'name' => 'Osaka Castle Duplicate',
            'latitude' => 34.6873,
            'longitude' => 135.5262,
            'category' => 'Tourism'
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Place already exists');

        // Should only have one place with these coordinates
        $this->assertCount(1, Place::where('lat', 34.6873)->where('lng', 135.5262)->get());
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
        $response = $this->actingAs($this->user)->postJson('/api/places/search-nearby', [
            'query' => '카페'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /** @test */
    public function search_nearby_validates_coordinate_ranges()
    {
        $response = $this->actingAs($this->user)->postJson('/api/places/search-nearby', [
            'latitude' => 91, // Invalid: max 90
            'longitude' => 181, // Invalid: max 180
            'query' => '카페'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /** @test */
    public function search_nearby_validates_radius_range()
    {
        $response = $this->actingAs($this->user)->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'radius' => 50000, // Invalid: max 10000 meters
            'query' => '카페'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    }

    /** @test */
    public function search_nearby_uses_default_radius_when_not_provided()
    {
        $this->mockNaverMapsSearchResponse([]);

        $response = $this->actingAs($this->user)->postJson('/api/places/search-nearby', [
            'latitude' => 37.4979,
            'longitude' => 127.0276,
            'query' => '카페'
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.radius', 1000); // Default radius
    }

    /** @test */
    public function search_text_requires_query_parameter()
    {
        $response = $this->actingAs($this->user)->postJson('/api/places/search', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['query']);
    }

    /** @test */
    public function place_details_returns_404_when_not_found()
    {
        $this->mockNaverPlaceDetailsNotFound();

        $response = $this->actingAs($this->user)->getJson('/api/places/naver/99999999');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Place not found on NAVER Maps');
    }

    /** @test */
    public function duplicate_naver_place_id_returns_existing_place()
    {
        $existingPlace = Place::factory()->create([
            'naver_place_id' => '22222',
            'name' => 'Existing Place'
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/places', [
            'naver_place_id' => '22222',
            'fetch_details' => false
        ]);

        $response->assertOk() // Returns existing instead of 422
            ->assertJsonPath('data.id', $existingPlace->id)
            ->assertJsonPath('message', 'Place already exists');

        $this->assertCount(1, Place::where('naver_place_id', '22222')->get());
    }

    // ============================================
    // Helper methods for mocking NAVER Maps API
    // ============================================

    private function mockNaverMapsSearchResponse(array $places): void
    {
        // Mock the HTTP client response for NAVER Local Search API
        Http::fake([
            'https://openapi.naver.com/v1/search/local.json*' => Http::response([
                'items' => $places,
                'total' => count($places)
            ], 200)
        ]);
    }

    private function mockNaverPlaceDetailsResponse(array $placeData): void
    {
        // Mock the HTTP client response for NAVER Place Details API
        // Note: NAVER doesn't have a direct place details API, so we use search + ID
        Http::fake([
            'https://openapi.naver.com/v1/search/local.json*' => Http::response([
                'items' => [
                    [
                        'title' => $placeData['name'],
                        'category' => $placeData['category'],
                        'address' => $placeData['address'],
                        'roadAddress' => $placeData['roadAddress'],
                        'mapx' => (string)($placeData['longitude'] * 10000000),
                        'mapy' => (string)($placeData['latitude'] * 10000000),
                        'telephone' => $placeData['phone'] ?? '',
                        'description' => $placeData['description'] ?? '',
                        'link' => $placeData['link'] ?? "https://place.naver.com/place/{$placeData['id']}"
                    ]
                ],
                'total' => 1
            ], 200)
        ]);
    }

    private function mockNaverPlaceDetailsNotFound(): void
    {
        Http::fake([
            'https://openapi.naver.com/v1/search/local.json*' => Http::response([
                'items' => [],
                'total' => 0
            ], 200)
        ]);
    }
}
