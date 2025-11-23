<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Amadeus\HotelService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HotelControllerTest extends TestCase
{

    private User $user;
    private MockInterface $hotelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make(['id' => 1]);
        $this->hotelService = Mockery::mock(HotelService::class);
        $this->app->instance(HotelService::class, $this->hotelService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_requires_authentication_for_hotel_search(): void
    {
        $response = $this->postJson('/api/hotels/search', [
            'search_type' => 'city',
            'city_code' => 'NYC',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_validates_search_payload(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['search_type']);
    }

    #[Test]
    public function user_can_search_hotels_by_city(): void
    {
        $payload = [
            'search_type' => 'city',
            'city_code' => 'NYC',
            'hotel_source' => 'GDS',
        ];

        $results = [
            ['hotelId' => 'NYC123', 'name' => 'Test Hotel 1'],
            ['hotelId' => 'NYC456', 'name' => 'Test Hotel 2'],
        ];

        $this->hotelService->shouldReceive('searchHotelsByCity')
            ->once()
            ->with('NYC', ['hotelSource' => 'GDS'])
            ->andReturn($results);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search', $payload);

        $response->assertOk()
            ->assertJson([
                'data' => $results,
                'meta' => [
                    'search_type' => 'city',
                    'total' => count($results),
                ],
            ]);
    }

    #[Test]
    public function user_can_search_hotels_by_geocode(): void
    {
        $payload = [
            'search_type' => 'geocode',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius' => 10,
            'radius_unit' => 'MILE',
        ];

        $results = [
            ['hotelId' => 'GEO1', 'name' => 'Geo Hotel'],
        ];

        $this->hotelService->shouldReceive('searchHotelsByGeocode')
            ->once()
            ->with(40.7128, -74.0060, 10, 'MILE')
            ->andReturn($results);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function user_can_search_hotels_by_ids(): void
    {
        $payload = [
            'search_type' => 'hotel_ids',
            'hotel_ids' => ['ABC123', 'DEF456'],
        ];

        $results = [
            ['hotelId' => 'ABC123', 'name' => 'Hotel Alpha'],
        ];

        $this->hotelService->shouldReceive('searchHotelsByIds')
            ->once()
            ->with(['ABC123', 'DEF456'])
            ->andReturn($results);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function hotel_search_returns_service_unavailable_when_null(): void
    {
        $payload = [
            'search_type' => 'city',
            'city_code' => 'NYC',
        ];

        $this->hotelService->shouldReceive('searchHotelsByCity')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Hotel search service is currently unavailable',
                'data' => [],
            ]);
    }

    #[Test]
    public function user_can_search_hotel_offers(): void
    {
        $checkIn = now()->addMonth()->format('Y-m-d');
        $checkOut = now()->addMonth()->addDays(4)->format('Y-m-d');

        $payload = [
            'hotel_ids' => ['NYC123'],
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'adults' => 2,
            'room_quantity' => 1,
            'currency' => 'USD',
            'price_range' => '100-400',
            'payment_policy' => 'NONE',
            'board_type' => 'BREAKFAST',
            'include_closed' => true,
        ];

        $offers = [
            [
                'hotel' => ['hotelId' => 'NYC123', 'name' => 'Test Hotel'],
                'offers' => [
                    ['id' => 'OFFER1', 'price' => ['total' => '300.00']],
                ],
            ],
        ];

        $this->hotelService->shouldReceive('getHotelOffers')
            ->once()
            ->with(
                ['NYC123'],
                $checkIn,
                $checkOut,
                [
                    'adults' => 2,
                    'roomQuantity' => 1,
                    'currency' => 'USD',
                    'priceRange' => '100-400',
                    'paymentPolicy' => 'NONE',
                    'boardType' => 'BREAKFAST',
                    'includeClosed' => true,
                ]
            )
            ->andReturn($offers);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/offers', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.total_hotels', 1)
            ->assertJsonPath('data.0.offers.0.id', 'OFFER1');
    }

    #[Test]
    public function hotel_offers_endpoint_handles_service_failure(): void
    {
        $checkIn = now()->addMonth()->format('Y-m-d');
        $checkOut = now()->addMonth()->addDays(2)->format('Y-m-d');

        $payload = [
            'hotel_ids' => ['NYC123'],
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
        ];

        $this->hotelService->shouldReceive('getHotelOffers')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/offers', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Hotel offers service is currently unavailable',
                'data' => [],
            ]);
    }

    #[Test]
    public function it_can_show_hotel_offer_details(): void
    {
        $offer = [
            'id' => 'OFFER123',
            'price' => ['total' => '450.00'],
        ];

        $this->hotelService->shouldReceive('getHotelOfferById')
            ->once()
            ->with('OFFER123')
            ->andReturn($offer);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/hotels/offers/OFFER123');

        $response->assertOk()
            ->assertJson(['data' => $offer]);
    }

    #[Test]
    public function show_offer_returns_not_found_when_service_returns_null(): void
    {
        $this->hotelService->shouldReceive('getHotelOfferById')
            ->once()
            ->with('MISSING')
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/hotels/offers/MISSING');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Hotel offer not found or service unavailable',
            ]);
    }

    #[Test]
    public function user_can_fetch_hotel_ratings(): void
    {
        $payload = [
            'hotel_ids' => ['NYC123', 'NYC456'],
        ];

        $ratings = [
            ['hotelId' => 'NYC123', 'overallRating' => 4.5],
        ];

        $this->hotelService->shouldReceive('getHotelRatings')
            ->once()
            ->with(['NYC123', 'NYC456'])
            ->andReturn($ratings);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/ratings', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function hotel_ratings_endpoint_handles_service_failure(): void
    {
        $payload = [
            'hotel_ids' => ['NYC123'],
        ];

        $this->hotelService->shouldReceive('getHotelRatings')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/ratings', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Hotel ratings service is currently unavailable',
                'data' => [],
            ]);
    }

    #[Test]
    public function user_can_create_hotel_booking(): void
    {
        $payload = [
            'offer_id' => 'OFFER123',
            'guests' => [
                [
                    'name' => 'John Doe',
                    'contact' => [
                        'phone' => '+1234567890',
                        'email' => 'john@example.com',
                    ],
                ],
            ],
            'payment' => [
                'method' => 'CREDIT_CARD',
                'card' => [
                    'vendor_code' => 'VI',
                    'card_number' => '4111111111111111',
                    'expiry_date' => '12/30',
                    'card_holder_name' => 'John Doe',
                    'card_type' => 'CREDIT',
                ],
            ],
        ];

        $bookingResponse = [
            'id' => 'BOOKING123',
            'status' => 'CONFIRMED',
        ];

        $this->hotelService->shouldReceive('createHotelBooking')
            ->once()
            ->with([
                'offerId' => 'OFFER123',
                'guests' => $payload['guests'],
                'payments' => [
                    [
                        'method' => 'CREDIT_CARD',
                        'card' => $payload['payment']['card'],
                    ],
                ],
            ])
            ->andReturn($bookingResponse);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/bookings', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.id', 'BOOKING123')
            ->assertJsonPath('message', 'Hotel booking created successfully');
    }

    #[Test]
    public function hotel_booking_endpoint_handles_service_failure(): void
    {
        $payload = [
            'offer_id' => 'OFFER123',
            'guests' => [
                [
                    'name' => 'John Doe',
                    'contact' => [
                        'phone' => '+1234567890',
                        'email' => 'john@example.com',
                    ],
                ],
            ],
            'payment' => [
                'method' => 'CREDIT_CARD',
                'card' => [
                    'vendor_code' => 'VI',
                    'card_number' => '4111111111111111',
                    'expiry_date' => '12/30',
                    'card_holder_name' => 'John Doe',
                    'card_type' => 'CREDIT',
                ],
            ],
        ];

        $this->hotelService->shouldReceive('createHotelBooking')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/bookings', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Hotel booking service is currently unavailable or booking failed',
            ]);
    }

    #[Test]
    public function user_can_search_hotels_with_offers(): void
    {
        $checkIn = now()->addMonths(2)->format('Y-m-d');
        $checkOut = now()->addMonths(2)->addDays(2)->format('Y-m-d');

        $payload = [
            'latitude' => 37.5665,
            'longitude' => 126.9780,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'radius' => 15,
            'radius_unit' => 'KM',
            'adults' => 2,
            'room_quantity' => 1,
            'currency' => 'KRW',
        ];

        $results = [
            'hotels' => [
                ['hotelId' => 'SEL123', 'name' => 'Seoul Hotel'],
            ],
            'offers' => [
                ['id' => 'OFFERSEL', 'price' => ['total' => '500000']],
            ],
        ];

        $this->hotelService->shouldReceive('searchHotelsWithOffers')
            ->once()
            ->with(
                37.5665,
                126.9780,
                $checkIn,
                $checkOut,
                [
                    'radius' => 15,
                    'radiusUnit' => 'KM',
                    'adults' => 2,
                    'roomQuantity' => 1,
                    'currency' => 'KRW',
                ]
            )
            ->andReturn($results);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search-with-offers', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.total_hotels', 1)
            ->assertJsonPath('meta.total_offers', 1);
    }

    #[Test]
    public function combined_search_returns_service_unavailable_when_null(): void
    {
        $checkIn = now()->addMonths(2)->format('Y-m-d');
        $checkOut = now()->addMonths(2)->addDays(2)->format('Y-m-d');

        $payload = [
            'latitude' => 37.5665,
            'longitude' => 126.9780,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
        ];

        $this->hotelService->shouldReceive('searchHotelsWithOffers')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/hotels/search-with-offers', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Hotel search service is currently unavailable',
                'data' => [
                    'hotels' => [],
                    'offers' => [],
                ],
            ]);
    }
}

