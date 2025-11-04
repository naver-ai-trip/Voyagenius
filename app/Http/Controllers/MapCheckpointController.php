<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapCheckpointRequest;
use App\Http\Requests\UpdateMapCheckpointRequest;
use App\Http\Resources\MapCheckpointResource;
use App\Models\MapCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MapCheckpointController extends Controller
{
    /**
     * Display a listing of checkpoints.
     * Supports filtering by trip_id and checked_in status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MapCheckpoint::query();

        // Filter by trip_id (required)
        if ($request->has('trip_id')) {
            $query->forTrip($request->input('trip_id'));
        }

        // Filter by checked_in status
        if ($request->has('checked_in') && $request->boolean('checked_in')) {
            $query->checkedIn();
        }

        // Load relationships
        $query->with(['place']);

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $checkpoints = $query->paginate($perPage);

        return MapCheckpointResource::collection($checkpoints);
    }

    /**
     * Store a newly created checkpoint.
     */
    public function store(StoreMapCheckpointRequest $request): MapCheckpointResource
    {
        $checkpoint = MapCheckpoint::create([
            'trip_id' => $request->input('trip_id'),
            'user_id' => $request->user()->id,
            'place_id' => $request->input('place_id'),
            'title' => $request->input('title'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'note' => $request->input('note'),
            'checked_in_at' => $request->input('checked_in_at'),
        ]);

        return new MapCheckpointResource($checkpoint);
    }

    /**
     * Display the specified checkpoint.
     */
    public function show(MapCheckpoint $checkpoint): MapCheckpointResource
    {
        // Load trip relationship for authorization
        $checkpoint->load('trip', 'place');
        
        $this->authorize('view', $checkpoint);

        return new MapCheckpointResource($checkpoint);
    }

    /**
     * Update the specified checkpoint.
     */
    public function update(UpdateMapCheckpointRequest $request, MapCheckpoint $checkpoint): MapCheckpointResource
    {
        // Load trip relationship for authorization
        $checkpoint->load('trip');
        
        $this->authorize('update', $checkpoint);

        $checkpoint->update($request->only([
            'trip_id',
            'place_id',
            'title',
            'lat',
            'lng',
            'note',
            'checked_in_at',
        ]));

        return new MapCheckpointResource($checkpoint);
    }

    /**
     * Remove the specified checkpoint.
     */
    public function destroy(MapCheckpoint $checkpoint): Response
    {
        // Load trip relationship for authorization
        $checkpoint->load('trip');
        
        $this->authorize('delete', $checkpoint);

        $checkpoint->delete();

        return response()->noContent();
    }
}
