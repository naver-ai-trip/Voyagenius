<?php

namespace Tests\Integration;

use App\Services\Amadeus\HotelService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Amadeus API Integration Tests - Real API Calls
 *
 * These tests are intended to hit the real Amadeus Travel APIs to verify:
 * - Credentials are working
 * - OAuth token negotiation succeeds
 * - Core hotel search, offers, ratings, and booking endpoints return real data
 *
 * Services tested:
 * - Amadeus API: Hotel search, offers, ratings, and bookings
 *
 * ⚠️ NOTE: These tests should be run manually with `--group=integration`
 * to avoid consuming API quota in normal CI runs.
 */
class AmadeusApiIntegrationTest extends TestCase
{
    private function resolveHotelService(): HotelService
    {
        $service = new HotelService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Amadeus Hotel service is not enabled or credentials are missing.');
        }

        return $service;
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_search_hotels_by_city(): void
    {
        $service = $this->resolveHotelService();

        $results = $service->searchHotelsByCity('NYC');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Expected at least one hotel for NYC search.');

        $hotel = $results[0];
        $this->assertArrayHasKey('hotelId', $hotel);
        $this->assertArrayHasKey('name', $hotel);
        $this->assertArrayHasKey('geoCode', $hotel);

        dump([
            '✅ Amadeus Hotel Search (City)' => 'SUCCESS',
            'input' => 'NYC',
            'returned' => count($results) . ' hotels',
            'sample' => array_slice($results, 0, 3),
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_search_hotels_by_geocode(): void
    {
        $service = $this->resolveHotelService();

        $results = $service->searchHotelsByGeocode(48.8566, 2.3522, 5, 'KM'); // Paris

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Expected hotels near Paris geocode.');

        $hotel = $results[0];
        $this->assertArrayHasKey('hotelId', $hotel);
        $this->assertArrayHasKey('name', $hotel);
        $this->assertArrayHasKey('geoCode', $hotel);

        dump([
            '✅ Amadeus Hotel Search (Geocode)' => 'SUCCESS',
            'input' => ['lat' => 48.8566, 'lng' => 2.3522],
            'radius_km' => 5,
            'returned' => count($results) . ' hotels',
            'sample' => array_slice($results, 0, 3),
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_fetch_offer_details_and_create_booking_for_real_hotels(): void
    {
        $service = $this->resolveHotelService();

        $cityHotels = $service->searchHotelsByCity('NYC');
        $this->assertNotEmpty($cityHotels, 'Expected hotels to fetch offers for.');

        $hotelIds = array_values(array_filter(array_column($cityHotels, 'hotelId')));
        $this->assertNotEmpty($hotelIds, 'City search should yield hotel IDs.');

        $checkIn = now()->format('Y-m-d');
        $checkOut = now()->addDays(1)->format('Y-m-d');
        dump($checkIn, $checkOut);

        $offers = $service->getHotelOffers(
            array_slice($hotelIds, 0, 10),
            $checkIn,
            $checkOut,
            [
                'adults' => 1,
                'roomQuantity' => 1,
                'currency' => 'USD',
            ]
        );

        $this->assertIsArray($offers);
        $this->assertNotEmpty($offers, 'Expected at least one hotel offer.');

        $hotelWithOffers = $offers[0];
        $this->assertArrayHasKey('offers', $hotelWithOffers);
        $this->assertNotEmpty($hotelWithOffers['offers'], 'Expected at least one offer for hotel.');

        $offerId = $hotelWithOffers['offers'][0]['id'] ?? null;
        $this->assertNotEmpty($offerId, 'Offer response should include an offer id.');

        $offerDetails = $service->getHotelOfferById($offerId);
        dump($offerDetails);

        $this->assertIsArray($offerDetails);
        $this->assertArrayHasKey('offers', $offerDetails);
        $this->assertNotEmpty($offerDetails['offers'], 'Expected at least one offer detail.');
        $this->assertEquals($offerId, $offerDetails['offers'][0]['id']);
        $this->assertArrayHasKey('price', $offerDetails['offers'][0]);

        dump([
            '✅ Amadeus Hotel Offers' => 'SUCCESS',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'hotel_ids' => array_slice($hotelIds, 0, 10),
            'sample_offer' => [
                'offer_id' => $offerId,
                'total' => $offerDetails['price']['total'] ?? null,
                'currency' => $offerDetails['price']['currency'] ?? null,
            ],
        ]);

        // Create hotel booking using the offer ID
        $bookingData = [
            'type' => 'hotel-order',
            'guests' => [
                [
                    'tid' => 1,
                    'title' => 'MR',
                    'firstName' => 'TEST',
                    'lastName' => 'GUEST',
                    'phone' => '+1234567890',
                    'email' => 'test.guest@example.com',
                ],
            ],
            'travelAgent' => [
                'contact' => [
                    'email' => 'test.guest@example.com',
                ],
            ],
            'roomAssociations' => [
                [
                    'hotelOfferId' => $offerId,
                    'guestReferences' => [
                        [
                            'guestReference' => '1',
                        ],
                    ],
                ],
            ],
            'payment' => [
                'method' => 'CREDIT_CARD',
                'paymentCard' => [
                    'paymentCardInfo' => [
                        'vendorCode' => 'VI',
                        'cardNumber' => '4151289722471370',
                        'expiryDate' => now()->addYears(2)->format('Y-m'),
                        'holderName' => 'TEST GUEST',
                    ],
                ],
            ],
        ];

        $booking = $service->createHotelBooking($bookingData);

        $this->assertIsArray($booking, 'Booking should return an array response.');
        $this->assertArrayHasKey('type', $booking);
        $this->assertEquals('hotel-order', $booking['type']);
        $this->assertArrayHasKey('id', $booking, 'Booking should have an order ID.');
        $this->assertArrayHasKey('hotelBookings', $booking, 'Booking should have hotel bookings array.');

        if (!empty($booking['hotelBookings'])) {
            $hotelBooking = $booking['hotelBookings'][0];
            $this->assertArrayHasKey('bookingStatus', $hotelBooking);
            $this->assertArrayHasKey('id', $hotelBooking);
        }

        dump([
            '✅ Amadeus Hotel Booking' => 'SUCCESS',
            'order_id' => $booking['id'] ?? null,
            'booking_status' => $booking['hotelBookings'][0]['bookingStatus'] ?? null,
            'offer_id_used' => $offerId,
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_search_hotels_with_offers(): void
    {
        $service = $this->resolveHotelService();

        // Search for hotels with offers in Paris
        $latitude = 48.8566;
        $longitude = 2.3522;
        $checkIn = now()->addMonths(1)->format('Y-m-d');
        $checkOut = now()->addMonths(1)->addDays(2)->format('Y-m-d');

        $results = $service->searchHotelsWithOffers(
            $latitude,
            $longitude,
            $checkIn,
            $checkOut,
            [
                'radius' => 5,
                'radiusUnit' => 'KM',
                'adults' => 1,
                'roomQuantity' => 1,
                'currency' => 'EUR',
            ]
        );

        $this->assertIsArray($results);
        $this->assertArrayHasKey('hotels', $results);
        $this->assertArrayHasKey('offers', $results);
        $this->assertIsArray($results['hotels']);
        $this->assertIsArray($results['offers']);

        // Validate hotels structure
        if (!empty($results['hotels'])) {
            $hotel = $results['hotels'][0];
            $this->assertArrayHasKey('hotelId', $hotel);
            $this->assertArrayHasKey('name', $hotel);
            $this->assertArrayHasKey('geoCode', $hotel);
        }

        // Validate offers structure
        if (!empty($results['offers'])) {
            $hotelWithOffers = $results['offers'][0];
            $this->assertArrayHasKey('offers', $hotelWithOffers);
            $this->assertIsArray($hotelWithOffers['offers']);

            if (!empty($hotelWithOffers['offers'])) {
                $offer = $hotelWithOffers['offers'][0];
                $this->assertArrayHasKey('id', $offer);
                $this->assertArrayHasKey('price', $offer);
            }
        }

        dump([
            '✅ Amadeus Search Hotels with Offers' => 'SUCCESS',
            'location' => ['lat' => $latitude, 'lng' => $longitude],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'hotels_found' => count($results['hotels']),
            'hotels_with_offers' => count($results['offers']),
            'sample' => [
                'hotel' => $results['hotels'][0] ?? null,
                'offer' => $results['offers'][0]['offers'][0] ?? null,
            ],
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_get_hotel_ratings(): void
    {
        $service = $this->resolveHotelService();

        // First, get some hotel IDs from a city search
        $cityHotels = $service->searchHotelsByCity('NYC');
        $this->assertNotEmpty($cityHotels, 'Expected hotels to fetch ratings for.');

        $hotelIds = array_values(array_filter(array_column($cityHotels, 'hotelId')));
        $this->assertNotEmpty($hotelIds, 'City search should yield hotel IDs.');

        // Get ratings for up to 3 hotels (API limit)
        $hotelIdsForRatings = array_slice($hotelIds, 0, 3);
        $ratings = $service->getHotelRatings($hotelIdsForRatings);

        $this->assertIsArray($ratings);
        $this->assertNotEmpty($ratings, 'Expected at least one hotel rating.');

        // Validate rating structure
        $rating = $ratings[0];
        $this->assertArrayHasKey('hotelId', $rating);
        $this->assertArrayHasKey('overallRating', $rating);

        // Check for sentiment data if available
        if (isset($rating['sentiments'])) {
            $this->assertIsArray($rating['sentiments']);
        }

        dump([
            '✅ Amadeus Hotel Ratings' => 'SUCCESS',
            'hotel_ids_requested' => $hotelIdsForRatings,
            'ratings_returned' => count($ratings),
            'sample_ratings' => array_map(function ($rating) {
                return [
                    'hotel_id' => $rating['hotelId'] ?? null,
                    'overall_rating' => $rating['overallRating'] ?? null,
                    'sentiments_count' => isset($rating['sentiments']) ? count($rating['sentiments']) : 0,
                ];
            }, array_slice($ratings, 0, 3)),
        ]);
    }
}
