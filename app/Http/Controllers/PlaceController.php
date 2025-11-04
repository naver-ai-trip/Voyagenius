<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchNearbyPlacesRequest;
use App\Http\Requests\SearchPlacesRequest;
use App\Http\Requests\StorePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\Naver\LocalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * PlaceController
 *
 * Handles place search, retrieval, and management using NAVER Local Search API.
 */
class PlaceController extends Controller
{
    public function __construct(
        private LocalSearchService $localSearchService
    ) {
    }

    /**
     * Search for places using text query.
     *
     * POST /api/places/search
     */
    public function search(SearchPlacesRequest $request): JsonResponse
    {
        $results = $this->localSearchService->search(
            query: $request->input('query'),
            display: 5
        );

        if ($results === null) {
            return response()->json([
                'message' => 'Place search service is currently unavailable',
                'data' => []
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'query' => $request->input('query')
            ]
        ]);
    }

    /**
     * Search for nearby places by coordinates.
     *
     * POST /api/places/search-nearby
     */
    public function searchNearby(SearchNearbyPlacesRequest $request): JsonResponse
    {
        $radius = $request->getRadius();

        $results = $this->localSearchService->searchNearby(
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude'),
            radiusMeters: $radius,
            query: $request->input('query'),
            display: 10
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'search_location' => [
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                ],
                'radius' => $radius
            ]
        ]);
    }

    /**
     * Get place details by NAVER place ID.
     *
     * GET /api/places/naver/{naverPlaceId}
     */
    public function getByNaverId(string $naverPlaceId): JsonResponse
    {
        $placeDetails = $this->localSearchService->getPlaceDetails($naverPlaceId);

        if ($placeDetails === null) {
            return response()->json([
                'message' => 'Place not found on NAVER Maps'
            ], 404);
        }

        return response()->json([
            'data' => $placeDetails
        ]);
    }

    /**
     * Store a place from NAVER to database.
     *
     * POST /api/places
     */
    public function store(StorePlaceRequest $request): JsonResponse
    {
        $naverPlaceId = $request->input('naver_place_id');

        // Check if place already exists
        $existingPlace = Place::where('naver_place_id', $naverPlaceId)->first();

        if ($existingPlace) {
            return response()->json([
                'message' => 'Place already exists',
                'data' => new PlaceResource($existingPlace)
            ]);
        }

        // Fetch details from NAVER if requested
        if ($request->input('fetch_details', false)) {
            $placeDetails = $this->localSearchService->getPlaceDetails($naverPlaceId);

            if ($placeDetails === null) {
                return response()->json([
                    'message' => 'Unable to fetch place details from NAVER'
                ], 422);
            }

            $place = Place::create([
                'naver_place_id' => $naverPlaceId,
                'name' => $placeDetails['name'],
                'category' => $placeDetails['category'],
                'address' => $placeDetails['address'],
                'lat' => $placeDetails['latitude'],
                'lng' => $placeDetails['longitude'],
            ]);
        } else {
            // Create minimal place entry
            $place = Place::create([
                'naver_place_id' => $naverPlaceId,
                'name' => $request->input('name', 'Unknown Place'),
                'lat' => $request->input('latitude', 0),
                'lng' => $request->input('longitude', 0),
            ]);
        }

        return response()->json([
            'message' => 'Place saved successfully',
            'data' => new PlaceResource($place)
        ], 201);
    }

    /**
     * Display a listing of saved places.
     *
     * GET /api/places
     */
    public function index(): AnonymousResourceCollection
    {
        $places = Place::with('reviews')->paginate(15);

        return PlaceResource::collection($places);
    }

    /**
     * Display the specified place from database.
     *
     * GET /api/places/{id}
     */
    public function show(Place $place): PlaceResource
    {
        $place->load('reviews', 'favorites');

        return new PlaceResource($place);
    }

    /**
     * Update the specified place.
     *
     * PUT/PATCH /api/places/{id}
     */
    public function update(Request $request, Place $place): PlaceResource
    {
        $place->update($request->only([
            'name',
            'category',
            'address',
            'lat',
            'lng'
        ]));

        return new PlaceResource($place);
    }

    /**
     * Remove the specified place.
     *
     * DELETE /api/places/{id}
     */
    public function destroy(Place $place): JsonResponse
    {
        $place->delete();

        return response()->json([
            'message' => 'Place deleted successfully'
        ]);
    }
}

