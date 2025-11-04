<?php

namespace App\Services\Naver;

/**
 * NAVER Maps API Service
 *
 * Documentation: https://api.ncloud-docs.com/docs/ai-naver-mapsgeocoding
 *
 * Provides integration with NAVER Maps Platform for:
 * - Geocoding (address → coordinates)
 * - Reverse Geocoding (coordinates → address)
 * - Directions (route calculation)
 * - Distance Matrix
 */
class NaverMapsService extends NaverBaseService
{
    public function __construct()
    {
        parent::__construct(config('services.naver.maps'));
    }

    /**
     * Geocoding: Convert address to coordinates
     *
     * @param string $query Address or place name
     * @return array{address: string, roadAddress: string, latitude: float, longitude: float}|null
     */
    public function geocode(string $query): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->logApiCall('GET', 'geocode', ['query' => $query]);

        $response = $this->client()
            ->get('/map-geocode/v2/geocode', [
                'query' => $query,
            ]);

        $data = $this->handleResponse($response, 'geocode');

        if (empty($data['addresses'])) {
            return null;
        }

        $address = $data['addresses'][0];

        $latitude = (float) ($address['y'] ?? 0);
        $longitude = (float) ($address['x'] ?? 0);

        return [
            'address' => $address['jibunAddress'] ?? '',
            'roadAddress' => $address['roadAddress'] ?? '',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'lat' => $latitude,  // Alias for backward compatibility
            'lng' => $longitude, // Alias for backward compatibility
        ];
    }

    /**
     * Reverse Geocoding: Convert coordinates to address
     *
     * @param float $latitude
     * @param float $longitude
     * @return array{address: string, roadAddress: string, area: array}|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->logApiCall('GET', 'reverse-geocode', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $coords = "{$longitude},{$latitude}";

        $response = $this->client()
            ->get('/map-reversegeocode/v2/gc', [
                'coords' => $coords,
                'output' => 'json',
                'orders' => 'roadaddr,addr',
            ]);

        $data = $this->handleResponse($response, 'reverse-geocode');

        if (empty($data['results'])) {
            return null;
        }

        $result = $data['results'][0];
        $region = $result['region'] ?? [];

        return [
            'address' => $result['land']['name'] ?? '',
            'roadAddress' => $result['land']['addition0']['value'] ?? '',
            'area' => [
                'level1' => $region['area1']['name'] ?? '', // Province/City
                'level2' => $region['area2']['name'] ?? '', // District
                'level3' => $region['area3']['name'] ?? '', // Neighborhood
                'level4' => $region['area4']['name'] ?? '', // Sub-neighborhood
            ],
        ];
    }

    /**
     * Calculate distance between two points
     *
     * @param float $fromLat
     * @param float $fromLng
     * @param float $toLat
     * @param float $toLng
     * @return array{distance: int, duration: int}|null Distance in meters, duration in seconds
     */
    public function getDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->logApiCall('GET', 'distance', [
            'from' => "{$fromLat},{$fromLng}",
            'to' => "{$toLat},{$toLng}",
        ]);

        $response = $this->client()
            ->get('/map-direction/v1/driving', [
                'start' => "{$fromLng},{$fromLat}",
                'goal' => "{$toLng},{$toLat}",
                'option' => 'trafast', // Traffic-optimized fastest route
            ]);

        $data = $this->handleResponse($response, 'distance');

        if (empty($data['route']['trafast'])) {
            return null;
        }

        $route = $data['route']['trafast'][0]['summary'];

        return [
            'distance' => (int) $route['distance'], // meters
            'duration' => (int) $route['duration'], // milliseconds
        ];
    }

    /**
     * Search for places/POIs near coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @param string $query Search query
     * @param int $radius Search radius in meters (max 5000)
     * @return array List of places
     */
    public function searchNearby(float $latitude, float $longitude, string $query = '', int $radius = 1000): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $this->logApiCall('GET', 'search-nearby', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'query' => $query,
            'radius' => $radius,
        ]);

        $response = $this->client()
            ->withHeaders([
                'X-Naver-Client-Id' => $this->clientId,
                'X-Naver-Client-Secret' => $this->clientSecret,
            ])
            ->get('https://openapi.naver.com/v1/search/local.json', [
                'query' => $query ?: '맛집',
                'display' => 20,
                'sort' => 'random',
            ]);

        $data = $this->handleResponse($response, 'search-nearby');

        return array_map(function ($item) {
            return [
                'title' => strip_tags($item['title']),
                'address' => $item['address'] ?? '',
                'roadAddress' => $item['roadAddress'] ?? '',
                'category' => $item['category'] ?? '',
                'latitude' => $item['mapy'] ? (float) ($item['mapy'] / 10000000) : null,
                'longitude' => $item['mapx'] ? (float) ($item['mapx'] / 10000000) : null,
                'phone' => $item['telephone'] ?? '',
                'link' => $item['link'] ?? '',
            ];
        }, $data['items'] ?? []);
    }
}
