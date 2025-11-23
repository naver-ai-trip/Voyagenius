<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Amadeus\FlightService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlightControllerTest extends TestCase
{
    private User $user;
    private MockInterface $flightService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make(['id' => 1]);
        $this->flightService = Mockery::mock(FlightService::class);
        $this->app->instance(FlightService::class, $this->flightService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->postJson('/api/flights/search', []);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_validates_request_payload(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/flights/search', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'origin_location_code',
                'destination_location_code',
                'departure_date',
                'adults',
            ]);
    }

    #[Test]
    public function user_can_search_flight_offers(): void
    {
        $payload = [
            'origin_location_code' => 'ICN',
            'destination_location_code' => 'JFK',
            'departure_date' => now()->addMonth()->format('Y-m-d'),
            'return_date' => now()->addMonths(2)->format('Y-m-d'),
            'adults' => 1,
            'children' => 1,
            'travel_class' => 'BUSINESS',
            'non_stop' => true,
            'currency_code' => 'USD',
            'max_price' => 1500,
            'max' => 20,
            'included_checked_bags_only' => true,
            'sources' => ['GDS'],
        ];

        $amadeusParams = [
            'originLocationCode' => 'ICN',
            'destinationLocationCode' => 'JFK',
            'departureDate' => $payload['departure_date'],
            'returnDate' => $payload['return_date'],
            'adults' => 1,
            'children' => 1,
            'travelClass' => 'BUSINESS',
            'nonStop' => true,
            'currencyCode' => 'USD',
            'maxPrice' => 1500,
            'max' => 20,
            'includedCheckedBagsOnly' => true,
            'sources' => 'GDS',
        ];

        $offers = [
            [
                'id' => '1',
                'itineraries' => [],
            ],
        ];

        $this->flightService->shouldReceive('searchFlightOffers')
            ->once()
            ->with($amadeusParams)
            ->andReturn($offers);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/flights/search', $payload);

        $response->assertOk()
            ->assertJsonPath('meta.total_offers', 1)
            ->assertJsonPath('data.0.id', '1');
    }

    #[Test]
    public function it_handles_service_failure(): void
    {
        $payload = [
            'origin_location_code' => 'ICN',
            'destination_location_code' => 'JFK',
            'departure_date' => now()->addMonth()->format('Y-m-d'),
            'adults' => 1,
        ];

        $expectedParams = [
            'originLocationCode' => 'ICN',
            'destinationLocationCode' => 'JFK',
            'departureDate' => $payload['departure_date'],
            'adults' => 1,
        ];

        $this->flightService->shouldReceive('searchFlightOffers')
            ->once()
            ->with($expectedParams)
            ->andReturn(null);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/flights/search', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Flight offers service is currently unavailable',
                'data' => [],
            ]);
    }
}

