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
     * Accept a recommendation.
     *
     * @OA\Post(
     *     path="/api/recommendations/{id}/accept",
     *     summary="Accept a recommendation",
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Recommendation accepted"),
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

        $recommendation->accept($request->user());

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
     *     tags={"AI Agent - Recommendations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Recommendation rejected"),
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

        $recommendation->reject($request->user());

        return response()->json([
            'data' => new TripRecommendationResource($recommendation),
        ]);
    }
}
