<?php

namespace Tests\Integration;

use App\Services\SerpAPI\FlightService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SerpAPI Integration Tests - Real API Calls
 *
 * These tests are intended to hit the real SerpAPI to verify:
 * - Credentials are working
 * - API key authentication succeeds
 * - Core flight search endpoints return real data
 *
 * Services tested:
 * - SerpAPI: Flight search via Google Flights
 *
 * ⚠️ NOTE: These tests should be run manually with `--group=integration`
 * to avoid consuming API quota in normal CI runs.
 */
class SerpApiIntegrationTest extends TestCase
{
    private function resolveFlightService(): FlightService
    {
        $service = new FlightService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('SerpAPI Flight service is not enabled or credentials are missing.');
        }

        return $service;
    }

    #[Test]
    #[Group('integration')]
    #[Group('serpapi')]
    public function it_can_search_flight_offers(): void
    {
        $service = $this->resolveFlightService();

        // Search for flights from Ho Chi Minh City to Seoul
        $outboundDate = now()->addDays(1)->format('Y-m-d');
        $returnDate = now()->addDays(7)->format('Y-m-d');

        $results = $service->searchFlightOffers(
            departureId: '/m/0hn4h',
            arrivalId: '/m/0hsqf',
            outboundDate: $outboundDate,
            returnDate: $returnDate
        );

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Expected at least one flight offer for HCM to Seoul.');

        $offer = $results[0];
        $this->assertArrayHasKey('id', $offer);
        $this->assertArrayHasKey('source', $offer);
        $this->assertArrayHasKey('itineraries', $offer);
        $this->assertArrayHasKey('price', $offer);

        // Validate itinerary structure
        $this->assertNotEmpty($offer['itineraries'], 'Offer should have at least one itinerary.');
        $itinerary = $offer['itineraries'][0];
        $this->assertArrayHasKey('segments', $itinerary);
        $this->assertNotEmpty($itinerary['segments'], 'Itinerary should have at least one segment.');

        // Validate price structure
        $price = $offer['price'];
        $this->assertArrayHasKey('total', $price);
        $this->assertArrayHasKey('currency', $price);

        dump([
            '✅ SerpAPI Flight Offers Search' => 'SUCCESS',
            'route' => 'LAX → AUS',
            'outbound_date' => $outboundDate,
            'return_date' => $returnDate,
            'returned' => count($results) . ' offers',
            'sample_offer' => [
                'id' => $offer['id'],
                'source' => $offer['source'],
                'price' => [
                    'total' => $price['total'],
                    'currency' => $price['currency'],
                ],
                'segments' => count($itinerary['segments']),
            ],
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('serpapi')]
    public function it_can_search_one_way_flight_offers(): void
    {
        $service = $this->resolveFlightService();

        // Search for one-way flights from New York to San Francisco, 2 months in the future
        $outboundDate = now()->addMonths(2)->format('Y-m-d');

        $results = $service->searchFlightOffers(
            departureId: 'JFK',
            arrivalId: 'SFO',
            outboundDate: $outboundDate,
            returnDate: null
        );

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Expected at least one one-way flight offer for JFK to SFO.');

        $offer = $results[0];
        $this->assertArrayHasKey('id', $offer);
        $this->assertArrayHasKey('itineraries', $offer);
        $this->assertArrayHasKey('price', $offer);

        // One-way flights should have only one itinerary
        $this->assertCount(1, $offer['itineraries'], 'One-way flight should have exactly one itinerary.');

        dump([
            '✅ SerpAPI One-Way Flight Offers' => 'SUCCESS',
            'route' => 'JFK → SFO (one-way)',
            'outbound_date' => $outboundDate,
            'returned' => count($results) . ' offers',
            'sample_offer' => [
                'id' => $offer['id'],
                'source' => $offer['source'] ?? 'SERPAPI',
                'price_total' => $offer['price']['total'] ?? null,
                'price_currency' => $offer['price']['currency'] ?? null,
            ],
        ]);
    }
}
