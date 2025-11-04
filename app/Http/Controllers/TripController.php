<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TripController extends Controller
{
    /**
     * Display a listing of the user's trips.
     * 
     * GET /api/trips
     * Query params: ?status=planning&per_page=15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Trip::where('user_id', $request->user()->id);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->input('per_page', 15);
        $trips = $query->latest()->paginate($perPage);

        return TripResource::collection($trips);
    }

    /**
     * Store a newly created trip.
     *
     * POST /api/trips
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = Trip::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'destination_country' => $request->input('destination_country'),
            'destination_city' => $request->input('destination_city'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status', 'planning'),
            'is_group' => $request->input('is_group', false),
            'progress' => $request->input('progress'),
        ]);

        return response()->json([
            'data' => new TripResource($trip),
            'message' => 'Trip created successfully',
        ], 201);
    }

    /**
     * Display the specified trip.
     *
     * GET /api/trips/{trip}
     */
    public function show(Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        return response()->json([
            'data' => new TripResource($trip),
        ]);
    }

    /**
     * Update the specified trip.
     *
     * PUT/PATCH /api/trips/{trip}
     */
    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $trip->update($request->only([
            'title',
            'destination_country',
            'destination_city',
            'start_date',
            'end_date',
            'status',
            'is_group',
            'progress',
        ]));

        return response()->json([
            'data' => new TripResource($trip),
            'message' => 'Trip updated successfully',
        ]);
    }

    /**
     * Remove the specified trip.
     *
     * DELETE /api/trips/{trip}
     */
    public function destroy(Trip $trip): JsonResponse
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json(null, 204);
    }
}
