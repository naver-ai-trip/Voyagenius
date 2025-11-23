<?php

namespace Tests\Unit\Services\Amadeus;

use App\Services\Amadeus\HotelService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HotelServiceTest extends TestCase
{
    private HotelService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test credentials
        config([
            'services.amadeus.api_key' => 'test_api_key',
            'services.amadeus.api_secret' => 'test_api_secret',
            'services.amadeus.base_url' => 'https://test.api.amadeus.com/v1',
            'services.amadeus.token_url' => 'https://test.api.amadeus.com/v1/security/oauth2/token',
            'services.amadeus.enabled' => true,
        ]);

        $this->service = new HotelService();
    }

    #[Test]
    public function it_can_check_if_service_is_enabled()
    {
        $this->assertTrue($this->service->isEnabled());

        // Test with disabled service
        config(['services.amadeus.enabled' => false]);
        $service = new HotelService();
        $this->assertFalse($service->isEnabled());
    }

    #[Test]
    public function it_returns_null_when_service_is_disabled()
    {
        config(['services.amadeus.enabled' => false]);
        $service = new HotelService();

        $this->assertNull($service->searchHotelsByCity('PAR'));
        $this->assertNull($service->searchHotelsByGeocode(48.8566, 2.3522));
        $this->assertNull($service->searchHotelsByIds(['RTPAR001']));
    }

    #[Test]
    public function it_can_search_hotels_by_city_code()
    {
        // Mock OAuth token request
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-city*' => Http::response([
                'data' => [
                    [
                        'type' => 'hotel',
                        'hotelId' => 'RTPAR001',
                        'chainCode' => 'RT',
                        'name' => 'Grand Hotel Paris',
                        'rating' => '4',
                        'cityCode' => 'PAR',
                        'latitude' => 48.8566,
                        'longitude' => 2.3522,
                        'address' => [
                            'lines' => ['123 Rue de la Paix'],
                            'cityName' => 'Paris',
                            'countryCode' => 'FR',
                        ],
                    ],
                    [
                        'type' => 'hotel',
                        'hotelId' => 'RTPAR002',
                        'name' => 'Hotel Central Paris',
                        'cityCode' => 'PAR',
                        'latitude' => 48.8600,
                        'longitude' => 2.3500,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->searchHotelsByCity('PAR');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('RTPAR001', $result[0]['hotelId']);
        $this->assertEquals('Grand Hotel Paris', $result[0]['name']);
        $this->assertEquals('PAR', $result[0]['cityCode']);

        // Verify request was made with correct params
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/reference-data/locations/hotels/by-city') &&
                   $request['cityCode'] === 'PAR';
        });
    }

    #[Test]
    public function it_can_search_hotels_by_geocode()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-geocode*' => Http::response([
                'data' => [
                    [
                        'type' => 'hotel',
                        'hotelId' => 'RTPAR001',
                        'name' => 'Grand Hotel Paris',
                        'latitude' => 48.8566,
                        'longitude' => 2.3522,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->searchHotelsByGeocode(48.8566, 2.3522, 5, 'KM');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('RTPAR001', $result[0]['hotelId']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/reference-data/locations/hotels/by-geocode') &&
                   $request['latitude'] == 48.8566 &&
                   $request['longitude'] == 2.3522 &&
                   $request['radius'] == 5 &&
                   $request['radiusUnit'] === 'KM';
        });
    }

    #[Test]
    public function it_can_search_hotels_by_ids()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-hotels*' => Http::response([
                'data' => [
                    [
                        'type' => 'hotel',
                        'hotelId' => 'RTPAR001',
                        'name' => 'Grand Hotel Paris',
                    ],
                    [
                        'type' => 'hotel',
                        'hotelId' => 'RTPAR002',
                        'name' => 'Hotel Central Paris',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->searchHotelsByIds(['RTPAR001', 'RTPAR002']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/reference-data/locations/hotels/by-hotels') &&
                   str_contains($request->url(), 'RTPAR001') &&
                   str_contains($request->url(), 'RTPAR002');
        });
    }

    #[Test]
    public function it_can_get_hotel_offers()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/v3/shopping/hotel-offers*' => Http::response([
                'data' => [
                    [
                        'type' => 'hotel-offers',
                        'hotel' => [
                            'hotelId' => 'RTPAR001',
                            'name' => 'Grand Hotel Paris',
                        ],
                        'offers' => [
                            [
                                'id' => 'ABC123XYZ',
                                'checkInDate' => '2024-12-25',
                                'checkOutDate' => '2024-12-27',
                                'price' => [
                                    'total' => '200.00',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getHotelOffers(
            ['RTPAR001'],
            '2024-12-25',
            '2024-12-27',
            ['adults' => 2, 'roomQuantity' => 1]
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('hotel-offers', $result[0]['type']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v3/shopping/hotel-offers') &&
                   str_contains($request->url(), 'RTPAR001') &&
                   str_contains($request->url(), '2024-12-25') &&
                   str_contains($request->url(), '2024-12-27');
        });
    }

    #[Test]
    public function it_can_get_hotel_offer_by_id()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/v3/shopping/hotel-offers/ABC123XYZ*' => Http::response([
                'data' => [
                    'type' => 'hotel-offers',
                    'hotel' => [
                        'hotelId' => 'RTPAR001',
                        'name' => 'Grand Hotel Paris',
                    ],
                    'offers' => [
                        [
                            'id' => 'ABC123XYZ',
                            'checkInDate' => '2024-12-25',
                            'checkOutDate' => '2024-12-27',
                            'price' => [
                                'total' => '200.00',
                                'currency' => 'EUR',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getHotelOfferById('ABC123XYZ');

        $this->assertIsArray($result);
        $this->assertEquals('hotel-offers', $result['type']);
        $this->assertEquals('RTPAR001', $result['hotel']['hotelId']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v3/shopping/hotel-offers/ABC123XYZ');
        });
    }

    #[Test]
    public function it_can_get_hotel_ratings()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/v2/e-reputation/hotel-sentiments*' => Http::response([
                'data' => [
                    [
                        'hotelId' => 'RTPAR001',
                        'rating' => 4.5,
                        'sentiment' => 'positive',
                    ],
                    [
                        'hotelId' => 'RTPAR002',
                        'rating' => 4.0,
                        'sentiment' => 'positive',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getHotelRatings(['RTPAR001', 'RTPAR002']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('RTPAR001', $result[0]['hotelId']);
        $this->assertEquals(4.5, $result[0]['rating']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v2/e-reputation/hotel-sentiments') &&
                   str_contains($request->url(), 'RTPAR001');
        });
    }

    #[Test]
    public function it_limits_hotel_ratings_to_max_3_hotels()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/v2/e-reputation/hotel-sentiments*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $this->service->getHotelRatings(['RTPAR001', 'RTPAR002', 'RTPAR003', 'RTPAR004']);

        Http::assertSent(function ($request) {
            // Should only include first 3 hotel IDs
            return str_contains($request->url(), 'RTPAR001') &&
                   str_contains($request->url(), 'RTPAR002') &&
                   str_contains($request->url(), 'RTPAR003') &&
                   !str_contains($request->url(), 'RTPAR004');
        });
    }

    #[Test]
    public function it_can_search_hotels_with_offers()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-geocode*' => Http::response([
                'data' => [
                    [
                        'hotelId' => 'RTPAR001',
                        'name' => 'Grand Hotel Paris',
                    ],
                    [
                        'hotelId' => 'RTPAR002',
                        'name' => 'Hotel Central Paris',
                    ],
                ],
            ], 200),
            '*/v3/shopping/hotel-offers*' => Http::response([
                'data' => [
                    [
                        'type' => 'hotel-offers',
                        'hotel' => ['hotelId' => 'RTPAR001'],
                        'offers' => [],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->searchHotelsWithOffers(
            48.8566,
            2.3522,
            '2024-12-25',
            '2024-12-27'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hotels', $result);
        $this->assertArrayHasKey('offers', $result);
        $this->assertCount(2, $result['hotels']);
        $this->assertCount(1, $result['offers']);
    }

    #[Test]
    public function it_returns_empty_array_when_no_hotels_found_in_search_with_offers()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-geocode*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $result = $this->service->searchHotelsWithOffers(
            48.8566,
            2.3522,
            '2024-12-25',
            '2024-12-27'
        );

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-city*' => Http::response([
                'errors' => [
                    [
                        'status' => 400,
                        'code' => 477,
                        'title' => 'INVALID FORMAT',
                        'detail' => 'Invalid city code',
                    ],
                ],
            ], 400),
        ]);

        $result = $this->service->searchHotelsByCity('INVALID');

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_oauth_token_fails()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401),
        ]);

        $result = $this->service->searchHotelsByCity('PAR');

        $this->assertNull($result);
    }

    #[Test]
    public function it_caches_oauth_token()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/reference-data/locations/hotels/by-city*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        // Clear cache
        Cache::forget('amadeus_access_token');

        // First call - should request token
        $this->service->searchHotelsByCity('PAR');

        // Second call - should use cached token
        $this->service->searchHotelsByCity('NYC');

        // Should only make one token request (first call) and two hotel searches
        $tokenRequests = collect(Http::recorded())->filter(function ($request) {
            return str_contains($request[0]->url(), '/security/oauth2/token');
        });

        $this->assertCount(1, $tokenRequests, 'Should only make one OAuth token request');
    }

    #[Test]
    public function it_can_create_hotel_booking()
    {
        Http::fake([
            '*/security/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 1799,
            ], 200),
            '*/booking/hotel-bookings*' => Http::response([
                'data' => [
                    'type' => 'hotel-booking',
                    'id' => 'BOOKING123',
                    'associatedRecords' => [
                        [
                            'reference' => 'CONFIRM123',
                            'creationDate' => '2024-12-01',
                        ],
                    ],
                ],
            ], 201),
        ]);

        $bookingData = [
            'offerId' => 'ABC123XYZ',
            'guests' => [
                [
                    'name' => [
                        'title' => 'MR',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                    ],
                    'contact' => [
                        'phone' => '+33123456789',
                        'email' => 'john@example.com',
                    ],
                ],
            ],
            'payments' => [
                [
                    'method' => 'CREDIT_CARD',
                    'card' => [
                        'vendorCode' => 'VI',
                        'cardNumber' => '4111111111111111',
                        'expiryDate' => '12/25',
                    ],
                ],
            ],
        ];

        $result = $this->service->createHotelBooking($bookingData);

        $this->assertIsArray($result);
        $this->assertEquals('hotel-booking', $result['type']);
        $this->assertEquals('BOOKING123', $result['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/booking/hotel-bookings') &&
                   $request->method() === 'POST';
        });
    }
}

