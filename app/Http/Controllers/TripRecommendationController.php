<?php

namespace App\Http\Controllers;

use App\Http\Resources\TripRecommendationResource;
use App\Models\Trip;
use App\Models\TripRecommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TripRecommendationController extends Controller
{
    /**
     * Display a listing of recommendations for a trip.
     * 
     * @OA\Get(
     *     path="/api/trips/{tripId}/recommendations",
     *     summary="List trip recommendations",
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tripId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status (pending, accepted, rejected)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[type]",
     *         in="query",
     *         description="Filter by recommendation type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[min_confidence]",
     *         in="query",
     *         description="Minimum confidence score",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index(Request $request, Trip $trip): AnonymousResourceCollection
    {
        $this->authorize('view', $trip);

        $query = TripRecommendation::where('trip_id', $trip->id);

        // Filter by status
        if ($request->has('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        // Filter by type
        if ($request->has('filter.type')) {
            $query->where('recommendation_type', $request->input('filter.type'));
        }

        // Filter by minimum confidence
        if ($request->has('filter.min_confidence')) {
            $query->where('confidence_score', '>=', $request->input('filter.min_confidence'));
        }

        $recommendations = $query->orderBy('confidence_score', 'desc')->get();

        return TripRecommendationResource::collection($recommendations);
    }

    /**
     * Display the specified recommendation.
     *
     * @OA\Get(
     *     path="/api/trips/{tripId}/recommendations/{id}",
     *     summary="Get recommendation details",
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tripId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Trip $trip, TripRecommendation $recommendation): TripRecommendationResource
    {
        $this->authorize('view', $trip);

        // Ensure recommendation belongs to this trip
        abort_if($recommendation->trip_id !== $trip->id, 404);

        return new TripRecommendationResource($recommendation);
    }

    /**
     * Store a newly created recommendation.
     *
     * @OA\Post(
     *     path="/api/trips/{tripId}/recommendations",
     *     summary="Create a new AI recommendation",
     *     description="AI agent creates a recommendation for the trip",
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tripId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Trip ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recommendation_type", "data", "confidence_score"},
     *             @OA\Property(
     *                 property="recommendation_type",
     *                 type="string",
     *                 enum={"place", "itinerary", "activity", "dining", "accommodation", "route"},
     *                 description="Type of recommendation",
     *                 example="place"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Recommendation details (structure varies by type)",
     *                 example={
     *                     "place_name": "Gyeongbokgung Palace",
     *                     "category": "Historical Site",
     *                     "description": "Largest of the Five Grand Palaces",
     *                     "coordinates": {"lat": 37.5788, "lng": 126.9770}
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="confidence_score",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 maximum=1,
     *                 description="AI confidence in this recommendation (0-1)",
     *                 example=0.92
     *             ),
     *             @OA\Property(
     *                 property="reasoning",
     *                 type="string",
     *                 nullable=true,
     *                 description="Explanation for why this was recommended",
     *                 example="Based on your interest in history and proximity to other sites"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Recommendation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1234),
     *                 @OA\Property(property="trip_id", type="integer"),
     *                 @OA\Property(property="recommendation_type", type="string"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="data", type="object"),
     *                 @OA\Property(property="confidence_score", type="number"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        $validated = $request->validate([
            'recommendation_type' => ['required', 'string', 'in:place,itinerary,activity,dining,accommodation,route'],
            'data' => ['required', 'array'],
            'confidence_score' => ['required', 'numeric', 'between:0,1'],
            'reasoning' => ['nullable', 'string', 'max:1000'],
        ]);

        $recommendation = TripRecommendation::create([
            'trip_id' => $trip->id,
            'recommendation_type' => $validated['recommendation_type'],
            'data' => $validated['data'],
            'confidence_score' => $validated['confidence_score'],
            'reasoning' => $validated['reasoning'] ?? null,
            'status' => 'pending',
        ]);

        return (new TripRecommendationResource($recommendation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Accept a recommendation.
     *
     * @OA\Post(
     *     path="/api/recommendations/{id}/accept",
     *     summary="Accept a recommendation",
     *     description="Accept AI recommendation and optionally add to itinerary",
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Recommendation ID"
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="note",
     *                 type="string",
     *                 nullable=true,
     *                 description="Optional note about why accepted",
     *                 example="Great suggestion! Adding to Day 2."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendation accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string", example="accepted"),
     *                 @OA\Property(property="accepted_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function accept(Request $request, TripRecommendation $recommendation): JsonResponse
    {
        $this->authorize('update', $recommendation->trip);

        if ($recommendation->status !== 'pending') {
            return response()->json([
                'message' => 'Recommendation has already been processed',
            ], 422);
        }

        $recommendation->accept();

        return response()->json([
            'data' => new TripRecommendationResource($recommendation),
        ]);
    }

    /**
     * Reject a recommendation.
     *
     * @OA\Post(
     *     path="/api/recommendations/{id}/reject",
     *     summary="Reject a recommendation",
     *     description="Reject AI recommendation with optional feedback",
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Recommendation ID"
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="feedback",
     *                 type="string",
     *                 nullable=true,
     *                 description="Reason for rejection (helps AI learn)",
     *                 example="Too expensive for my budget"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recommendation rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string", example="rejected"),
     *                 @OA\Property(property="rejected_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function reject(Request $request, TripRecommendation $recommendation): JsonResponse
    {
        $this->authorize('update', $recommendation->trip);

        if ($recommendation->status !== 'pending') {
            return response()->json([
                'message' => 'Recommendation has already been processed',
            ], 422);
        }

        $recommendation->reject();

        return response()->json([
            'data' => new TripRecommendationResource($recommendation),
        ]);
    }
}
