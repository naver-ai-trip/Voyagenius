<?php

namespace App\Services\Naver;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NAVER Local Search API Service
 *
 * Provides place search functionality using NAVER Local Search API.
 * Used for searching nearby places, getting place details, etc.
 */
class LocalSearchService
{
    private ?string $clientId;
    private ?string $clientSecret;
    private string $baseUrl = 'https://openapi.naver.com/v1/search/local.json';
    private bool $enabled;

    public function __construct()
    {
        $this->clientId = config('services.naver_developers.local_search.client_id');
        $this->clientSecret = config('services.naver_developers.local_search.client_secret');
        $this->enabled = config('services.naver_developers.local_search.enabled', false);
    }

    /**
     * Check if the service is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return $this->enabled
            && !empty($this->clientId)
            && !empty($this->clientSecret);
    }

    /**
     * Search for places using text query.
     *
     * @param string $query Search query (e.g., "강남역 카페")
     * @param int $display Number of results (1-5, default 5)
     * @return array|null Array of places or null if service disabled
     */
    public function search(string $query, int $display = 5): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('NAVER Local Search service is disabled');
            return null;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl, [
                    'query' => $query,
                    'display' => min($display, 5), 
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatResults($data['items'] ?? []);
            }

            Log::error('NAVER Local Search API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('NAVER Local Search exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Search for nearby places by coordinates and optional query.
     *
     * Note: NAVER Local Search API doesn't support direct coordinate+radius search.
     * This method searches by query and filters results by distance client-side.
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param int $radiusMeters Radius in meters
     * @param string|null $query Optional search query
     * @param int $display Number of results
     * @return array Array of places within radius
     */
    public function searchNearby(
        float $latitude,
        float $longitude,
        int $radiusMeters = 1000,
        ?string $query = null,
        int $display = 5
    ): array {
        if (!$this->isEnabled()) {
            Log::warning('NAVER Local Search service is disabled');
            return [];
        }

        // If no query provided, use generic search term
        $searchQuery = $query ?? '맛집'; // Default to restaurants

        $results = $this->search($searchQuery, $display);

        if ($results === null) {
            return [];
        }

        // Filter results by distance
        $filtered = array_filter($results, function ($place) use ($latitude, $longitude, $radiusMeters) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $place['latitude'],
                $place['longitude']
            );

            return $distance <= ($radiusMeters / 1000); // Convert meters to km
        });

        // Re-index array after filtering
        return array_values($filtered);
    }

    /**
     * Format NAVER API results to standardized format.
     *
     * @param array $items Raw items from NAVER API
     * @return array Formatted places
     */
    private function formatResults(array $items): array
    {
        return array_map(function ($item) {
            return [
                'name' => strip_tags($item['title'] ?? ''), // Remove HTML tags
                'category' => $item['category'] ?? '',
                'address' => $item['address'] ?? '',
                'road_address' => $item['roadAddress'] ?? ($item['address'] ?? ''),
                'latitude' => $this->convertCoordinate($item['mapy'] ?? '0'),
                'longitude' => $this->convertCoordinate($item['mapx'] ?? '0'),
                'phone' => $item['telephone'] ?? null,
                'naver_link' => $item['link'] ?? null,
                'description' => $item['description'] ?? null,
                'business_hours' => $item['businessHours'] ?? null,
            ];
        }, $items);
    }

    /**
     * Convert NAVER coordinate format to decimal degrees.
     * NAVER returns coordinates multiplied by 10,000,000.
     *
     * @param string $coordinate NAVER coordinate string
     * @return float Decimal coordinate
     */
    private function convertCoordinate(string $coordinate): float
    {
        return (float)$coordinate / 10000000;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     *
     * @param float $lat1 Latitude 1
     * @param float $lon1 Longitude 1
     * @param float $lat2 Latitude 2
     * @param float $lon2 Longitude 2
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get authentication headers for NAVER API.
     *
     * @return array Headers array
     */
    private function getHeaders(): array
    {
        return [
            'X-Naver-Client-Id' => $this->clientId,
            'X-Naver-Client-Secret' => $this->clientSecret,
        ];
    }
}
