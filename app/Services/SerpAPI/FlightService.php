<?php

namespace App\Services\SerpAPI;

use Illuminate\Support\Facades\Log;

/**
 * SerpAPI Flight Service
 *
 * Provides flight search functionality using SerpAPI Google Flights API.
 * Documentation: https://serpapi.com/google-flights-api
 */
class FlightService extends SerpAPIService
{
    public function __construct()
    {
        parent::__construct(config('services.serpapi', []));
    }

    /**
     * Search for flight offers using SerpAPI Google Flights
     *
     * @param string $departureId Departure airport IATA code (e.g., 'LAX', 'JFK')
     * @param string $arrivalId Arrival airport IATA code (e.g., 'AUS', 'SFO')
     * @param string $outboundDate Outbound date in YYYY-MM-DD format
     * @param string|null $returnDate Return date in YYYY-MM-DD format (optional for one-way flights)
     * @return array|null Flight offers data or null if error
     */
    public function searchFlightOffers(
        string $departureId,
        string $arrivalId,
        string $outboundDate,
        ?string $returnDate = null
    ): ?array {
        if (!$this->isEnabled()) {
            Log::warning('SerpAPI Flight service is disabled');
            return null;
        }

        $client = $this->client();

        $params = $this->buildParams([
            'engine' => 'google_flights',
            'departure_id' => $departureId,
            'arrival_id' => $arrivalId,
            'outbound_date' => $outboundDate,
        ]);

        if ($returnDate !== null) {
            $params['return_date'] = $returnDate;
        }

        $this->logApiCall('GET', $this->baseUrl, $params);

        try {
            $response = $client->get($this->baseUrl, $params);
            $data = $this->handleResponse($response, 'Flight Offers Search');

            dump($data);
            // Extract flights from SerpAPI response
            return $data;
        } catch (\Exception $e) {
            Log::error('SerpAPI Flight Offers Exception', [
                'message' => $e->getMessage()
            ]);
            dump($e->getMessage());
            return null;
        }
    }

    /**
     * Format SerpAPI response to match expected structure
     *
     * @param array $data Raw SerpAPI response
     * @return array Formatted flight offers
     */
    protected function formatFlightOffers(array $data): array
    {
        $offers = [];

        // Extract flights from SerpAPI response structure
        if (isset($data['flights_results']) && is_array($data['flights_results'])) {
            foreach ($data['flights_results'] as $index => $flightResult) {
                $offer = [
                    'id' => (string) ($index + 1),
                    'source' => 'SERPAPI',
                    'itineraries' => [],
                    'price' => [
                        'total' => (string) ($flightResult['price'] ?? '0'),
                        'currency' => 'USD', // SerpAPI typically returns USD
                    ],
                ];

                // Extract flight segments
                if (isset($flightResult['flights']) && is_array($flightResult['flights'])) {
                    $segments = [];
                    foreach ($flightResult['flights'] as $flight) {
                        $segment = [
                            'departure' => [
                                'iataCode' => $flight['departure_airport']['id'] ?? '',
                                'at' => $flight['departure_airport']['time'] ?? '',
                            ],
                            'arrival' => [
                                'iataCode' => $flight['arrival_airport']['id'] ?? '',
                                'at' => $flight['arrival_airport']['time'] ?? '',
                            ],
                            'carrierCode' => $this->extractCarrierCode($flight['airline'] ?? ''),
                            'number' => $flight['flight_number'] ?? '',
                            'aircraft' => [
                                'code' => $flight['airplane'] ?? '',
                            ],
                            'duration' => $this->formatDuration($flight['duration'] ?? 0),
                        ];

                        $segments[] = $segment;
                    }

                    $offer['itineraries'][] = [
                        'duration' => $this->formatDuration($flightResult['total_duration'] ?? 0),
                        'segments' => $segments,
                    ];
                }

                $offers[] = $offer;
            }
        }

        return $offers;
    }

    /**
     * Extract carrier code from airline name
     */
    protected function extractCarrierCode(string $airline): string
    {
        // Map common airline names to IATA codes
        $airlineMap = [
            'Southwest' => 'WN',
            'Delta' => 'DL',
            'American' => 'AA',
            'United' => 'UA',
            'Alaska' => 'AS',
            'JetBlue' => 'B6',
            'Spirit' => 'NK',
            'Frontier' => 'F9',
        ];

        return $airlineMap[$airline] ?? strtoupper(substr($airline, 0, 2));
    }

    /**
     * Format duration from minutes to ISO 8601 duration format
     */
    protected function formatDuration(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('PT%dH%dM', $hours, $mins);
    }
}
