<?php

namespace Tests\Unit\Services;

use App\Exceptions\NaverApiException;
use App\Services\Naver\NaverMapsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test suite for NAVER Maps API Service
 *
 * NAVER API Integration Notes:
 * - These tests use HTTP fakes to stub NAVER Maps API responses
 * - Real API calls are NOT made during testing
 * - Production code WILL make real API calls when enabled
 * - Authentication uses X-NCP-APIGW-API-KEY-ID and X-NCP-APIGW-API-KEY headers
 */
class NaverMapsServiceTest extends TestCase
{
    private NaverMapsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure NAVER Maps service for testing
        Config::set('services.naver.maps', [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'base_url' => 'https://naveropenapi.apigw.ntruss.com',
            'enabled' => true,
        ]);

        $this->service = new NaverMapsService();
    }

    /** @test */
    public function it_can_geocode_address_to_coordinates(): void
    {
        // Stub NAVER Geocoding API response
        Http::fake([
            '*/map-geocode/v2/geocode*' => Http::response([
                'status' => 'OK',
                'addresses' => [
                    [
                        'jibunAddress' => '서울특별시 강남구 역삼동 123-45',
                        'roadAddress' => '서울특별시 강남구 테헤란로 123',
                        'x' => '127.0276',
                        'y' => '37.4979',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->geocode('서울 강남구 역삼동');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('roadAddress', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertEquals('서울특별시 강남구 역삼동 123-45', $result['address']);
        $this->assertEquals('서울특별시 강남구 테헤란로 123', $result['roadAddress']);
        $this->assertEquals(37.4979, $result['latitude']);
        $this->assertEquals(127.0276, $result['longitude']);
    }

    /** @test */
    public function it_returns_null_when_no_geocoding_results_found(): void
    {
        Http::fake([
            '*/map-geocode/v2/geocode*' => Http::response([
                'status' => 'OK',
                'addresses' => [],
            ], 200),
        ]);

        $result = $this->service->geocode('NonexistentAddress12345');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_reverse_geocode_coordinates_to_address(): void
    {
        // Stub NAVER Reverse Geocoding API response
        Http::fake([
            '*/map-reversegeocode/v2/gc*' => Http::response([
                'status' => [
                    'code' => 0,
                    'name' => 'ok',
                ],
                'results' => [
                    [
                        'region' => [
                            'area1' => ['name' => '서울특별시'],
                            'area2' => ['name' => '강남구'],
                            'area3' => ['name' => '역삼동'],
                            'area4' => ['name' => ''],
                        ],
                        'land' => [
                            'name' => '역삼동 123-45',
                            'addition0' => ['value' => '테헤란로 123'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->reverseGeocode(37.4979, 127.0276);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('roadAddress', $result);
        $this->assertArrayHasKey('area', $result);
        $this->assertEquals('역삼동 123-45', $result['address']);
        $this->assertEquals('테헤란로 123', $result['roadAddress']);
        $this->assertEquals('서울특별시', $result['area']['level1']);
        $this->assertEquals('강남구', $result['area']['level2']);
        $this->assertEquals('역삼동', $result['area']['level3']);
    }

    /** @test */
    public function it_can_calculate_distance_between_two_points(): void
    {
        // Stub NAVER Directions API response
        Http::fake([
            '*/map-direction/v1/driving*' => Http::response([
                'code' => 0,
                'route' => [
                    'trafast' => [
                        [
                            'summary' => [
                                'distance' => 5234,
                                'duration' => 900000,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDistance(37.5665, 126.9780, 37.4979, 127.0276);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertEquals(5234, $result['distance']);
        $this->assertEquals(900000, $result['duration']);
    }

    /** @test */
    public function it_can_search_nearby_places(): void
    {
        // Stub NAVER Local Search API response
        Http::fake([
            'https://openapi.naver.com/v1/search/local.json*' => Http::response([
                'items' => [
                    [
                        'title' => '<b>맛있는 식당</b>',
                        'address' => '서울특별시 강남구 역삼동 123-45',
                        'roadAddress' => '서울특별시 강남구 테헤란로 123',
                        'category' => '음식점>한식>고깃집',
                        'mapx' => '1270276000',
                        'mapy' => '374979000',
                        'telephone' => '02-1234-5678',
                        'link' => 'https://example.com',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->searchNearby(37.4979, 127.0276, '맛집');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('맛있는 식당', $result[0]['title']);
        $this->assertEquals('서울특별시 강남구 역삼동 123-45', $result[0]['address']);
        $this->assertEquals('음식점>한식>고깃집', $result[0]['category']);
        $this->assertEquals(37.4979, $result[0]['latitude']);
        $this->assertEquals(127.0276, $result[0]['longitude']);
        $this->assertEquals('02-1234-5678', $result[0]['phone']);
    }

    /** @test */
    public function it_returns_null_when_service_is_disabled(): void
    {
        Config::set('services.naver.maps.enabled', false);
        $service = new NaverMapsService();

        $this->assertNull($service->geocode('서울'));
        $this->assertNull($service->reverseGeocode(37.5665, 126.9780));
        $this->assertNull($service->getDistance(37.5665, 126.9780, 37.4979, 127.0276));
    }

    /** @test */
    public function it_returns_empty_array_when_searching_disabled(): void
    {
        Config::set('services.naver.maps.enabled', false);
        $service = new NaverMapsService();

        $result = $service->searchNearby(37.5665, 126.9780, 'test');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_throws_exception_on_api_error(): void
    {
        Http::fake([
            '*/map-geocode/v2/geocode*' => Http::response([
                'errorMessage' => 'Invalid API key',
            ], 401),
        ]);

        $this->expectException(NaverApiException::class);
        $this->expectExceptionMessage('NAVER API Error (geocode)');

        $this->service->geocode('서울');
    }

    /** @test */
    public function it_includes_authentication_headers_in_requests(): void
    {
        Http::fake();

        try {
            $this->service->geocode('서울');
        } catch (\Exception $e) {
            // Ignore exceptions, we just want to check headers
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID', 'test_client_id') &&
                   $request->hasHeader('X-NCP-APIGW-API-KEY', 'test_client_secret');
        });
    }
}
