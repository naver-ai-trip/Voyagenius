<?php

namespace App\Services\Amadeus;

use Illuminate\Support\Facades\Log;

/**
 * FlightService
 *
 * Provides access to the Amadeus Flight Offers Search API.
 * Documentation: https://developers.amadeus.com/self-service/category/flights/api-doc/flight-offers-search/api-reference
 */
class FlightService extends AmadeusService
{
    public function __construct()
    {
        parent::__construct(config('services.amadeus', []));
    }

    /**
     * Search flight offers.
     *
     * @param  array  $parameters Query parameters expected by Amadeus
     * @return array|null Flight offers or null if request failed/disabled
     */
    public function searchFlightOffers(string $originCity, string $destinationCity, string $departureDate, ?string $returnDate): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Flight service is disabled');
            return null;
        }

        $client = $this->client('v2');
        if (!$client) {
            return null;
        }

        $originIATA = $this->getIATACode($originCity);
        $destinationIATA = $this->getIATACode($destinationCity);

        if (empty($originIATA) || empty($destinationIATA)) {
            Log::error('Could not find IATA codes for provided cities', [
                'originCity' => $originCity,
                'destinationCity' => $destinationCity,
            ]);
            return null;
        }

        $parameters = [
            'originLocationCode' => $originIATA,
            'destinationLocationCode' => $destinationIATA,
            'departureDate' => $departureDate,
            'adults' => 1,
        ];

        if ($returnDate !== null) {
            $parameters['returnDate'] = $returnDate;
        }

        $this->logApiCall('GET', '/shopping/flight-offers', $parameters);

        try {
            $response = $client->get('/shopping/flight-offers', $parameters);
            $data = $this->handleResponse($response, 'Flight Offers Search');

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Flight Offers Exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getIATACode(string $city): string
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Flight service is disabled');
            return '';
        }

        $client = $this->client();

        if (!$client) {
            return '';
        }

        $parameters = [
            'subType' => 'CITY',
            'keyword' => $city,
            'view' => 'LIGHT'
        ];

        $this->logApiCall('GET', '/reference-data/locations', $parameters);

        try {
            $response = $client->get('/reference-data/locations', $parameters);
            $data = $this->handleResponse($response, 'Location IATA Lookup');

            if (!empty($data['data'][0]['iataCode'])) {
                return $data['data'][0]['iataCode'];
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Amadeus IATA Code Exception', [
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
