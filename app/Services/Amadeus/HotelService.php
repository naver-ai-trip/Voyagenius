<?php

namespace App\Services\Amadeus;

use Illuminate\Support\Facades\Log;

/**
 * Amadeus Hotel API Service
 *
 * Provides hotel search, ratings, and booking functionality using Amadeus Hotel APIs:
 * - Hotel Search (by location, hotels)
 * - Hotel Ratings
 * - Hotel Booking
 * - Hotel Offers
 */
class HotelService extends AmadeusService
{
    public function __construct()
    {
        parent::__construct(config('services.amadeus', []));
    }

    /**
     * Search for hotels by city code
     *
     * @param string $cityCode IATA city code (e.g., 'NYC', 'PAR', 'LON')
     * @param array $options Optional parameters (radius, radiusUnit, hotelSource, etc.)
     * @return array|null Array of hotels or null if error
     */
    public function searchHotelsByCity(string $cityCode, array $options = []): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        $client = $this->client();
        if (!$client) {
            return null;
        }

        $params = array_merge([
            'cityCode' => strtoupper($cityCode),
        ], $options);

        $this->logApiCall('GET', '/reference-data/locations/hotels/by-city', $params);

        try {
            $response = $client->get('/reference-data/locations/hotels/by-city', $params);
            $data = $this->handleResponse($response, 'Hotel Search by City');

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Search Exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Search for hotels by geographic coordinates
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param int $radius Search radius (default 5)
     * @param string $radiusUnit Radius unit: KM or MILE (default KM)
     * @return array|null Array of hotels or null if error
     */
    public function searchHotelsByGeocode(
        float $latitude,
        float $longitude,
        int $radius = 5,
        string $radiusUnit = 'KM'
    ): ?array {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        $client = $this->client();
        if (!$client) {
            return null;
        }

        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
            'radiusUnit' => strtoupper($radiusUnit),
        ];

        $this->logApiCall('GET', '/reference-data/locations/hotels/by-geocode', $params);

        try {
            $response = $client->get('/reference-data/locations/hotels/by-geocode', $params);
            $data = $this->handleResponse($response, 'Hotel Search by Geocode');

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Search by Geocode Exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Search for hotels by list of hotel IDs
     *
     * @param array $hotelIds Array of hotel IDs (Amadeus property codes)
     * @return array|null Array of hotels or null if error
     */
    public function searchHotelsByIds(array $hotelIds): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        $client = $this->client();
        if (!$client) {
            return null;
        }

        $params = [
            'hotelIds' => implode(',', $hotelIds),
        ];

        $this->logApiCall('GET', '/reference-data/locations/hotels/by-hotels', $params);

        try {
            $response = $client->get('/reference-data/locations/hotels/by-hotels', $params);
            $data = $this->handleResponse($response, 'Hotel Search by IDs');

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Search by IDs Exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get hotel offers (availability and pricing)
     *
     * @param array $hotelIds Array of hotel IDs
     * @param string $checkInDate Check-in date (YYYY-MM-DD)
     * @param string $checkOutDate Check-out date (YYYY-MM-DD)
     * @param array $options Optional parameters (adults, roomQuantity, currency, etc.)
     * @return array|null Array of hotel offers or null if error
     */
    public function getHotelOffers(
        array $hotelIds,
        string $checkInDate,
        string $checkOutDate,
        array $options = []
    ): ?array {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        $client = $this->client('v3');
        if (!$client) {
            return null;
        }

        $params = array_merge([
            'hotelIds' => implode(',', $hotelIds),
            'checkInDate' => $checkInDate,
            'checkOutDate' => $checkOutDate,
            'adults' => 1,
            'roomQuantity' => 1,
        ], $options);

        $this->logApiCall('GET', '/shopping/hotel-offers', $params);

        try {
            $response = $client->get('/shopping/hotel-offers', $params);
            $data = $this->handleResponse($response, 'Hotel Offers Search');

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Offers Exception', [
                'message' => $e->getMessage()
            ]);
            dump($e->getMessage());
            return null;
        }
    }

    /**
     * Get hotel offer by offer ID
     *
     * @param string $offerId Offer ID from hotel offers search
     * @return array|null Hotel offer details or null if error
     */
    public function getHotelOfferById(string $offerId): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        $client = $this->client('v3');
        if (!$client) {
            return null;
        }

        $this->logApiCall('GET', "/shopping/hotel-offers/{$offerId}", []);

        try {
            $response = $client->get("/shopping/hotel-offers/{$offerId}");
            $data = $this->handleResponse($response, 'Hotel Offer Details');

            return $data['data'] ?? null;
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Offer Details Exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get hotel ratings and sentiments
     *
     * @param array $hotelIds Array of hotel IDs (max 3)
     * @return array|null Array of hotel ratings or null if error
     */
    public function getHotelRatings(array $hotelIds): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        $client = $this->client('v2');
        if (!$client) {
            return null;
        }

        // Amadeus allows max 3 hotels at once
        $hotelIds = array_slice($hotelIds, 0, 3);

        $params = [
            'hotelIds' => implode(',', $hotelIds),
        ];

        $this->logApiCall('GET', '/e-reputation/hotel-sentiments', $params);

        try {
            $response = $client->get('/e-reputation/hotel-sentiments', $params);
            $data = $this->handleResponse($response, 'Hotel Ratings');
            dump($data);

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Ratings Exception', [
                'message' => $e->getMessage()
            ]);
            dump($e->getMessage());
            return null;
        }
    }

    /**
     * Create a hotel booking
     *
     * @param array $bookingData Booking data matching Amadeus API schema:
     *   - type: "hotel-order"
     *   - guests: Array of guest objects with tid, title, firstName, lastName, phone, email
     *   - roomAssociations: Array with hotelOfferId and guestReferences
     *   - payment: Payment object with method, paymentCard
     *   - travelAgent: Optional travel agent contact
     * @return array|null Booking confirmation or null if error
     */
    public function createHotelBooking(array $bookingData): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Hotel service is disabled');
            return null;
        }

        // Use v2 client for hotel booking API
        $client = $this->client('v2');
        if (!$client) {
            return null;
        }

        // Ensure the data structure matches the API reference
        $requestBody = [
            'data' => $bookingData
        ];

        $this->logApiCall('POST', '/booking/hotel-orders', $requestBody);

        try {
            $response = $client->post('/booking/hotel-orders', $requestBody);
            $data = $this->handleResponse($response, 'Hotel Booking');
            dump($data);

            return $data['data'] ?? null;
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Booking Exception', [
                'message' => $e->getMessage()
            ]);
            dump($e->getMessage());
            return null;
        }
    }

    /**
     * Search hotels with offers in one call (combined search)
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $checkInDate Check-in date (YYYY-MM-DD)
     * @param string $checkOutDate Check-out date (YYYY-MM-DD)
     * @param array $options Optional parameters
     * @return array|null Combined hotel search with offers or null if error
     */
    public function searchHotelsWithOffers(
        float $latitude,
        float $longitude,
        string $checkInDate,
        string $checkOutDate,
        array $options = []
    ): ?array {
        // First, search for hotels by location
        $hotels = $this->searchHotelsByGeocode(
            $latitude,
            $longitude,
            $options['radius'] ?? 5,
            $options['radiusUnit'] ?? 'KM'
        );

        if (empty($hotels)) {
            return [];
        }

        // Extract hotel IDs (max 100 for offers API)
        $hotelIds = array_slice(
            array_column($hotels, 'hotelId'),
            0,
            100
        );

        // Get offers for these hotels
        $offers = $this->getHotelOffers($hotelIds, $checkInDate, $checkOutDate, $options);

        return [
            'hotels' => $hotels,
            'offers' => $offers ?? [],
        ];
    }
}
