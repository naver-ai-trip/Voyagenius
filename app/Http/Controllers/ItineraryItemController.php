<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItineraryItemRequest;
use App\Http\Requests\UpdateItineraryItemRequest;
use App\Http\Resources\ItineraryItemResource;
use App\Models\ItineraryItem;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItineraryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ItineraryItem::query()
            ->with(['trip', 'place']);

        // Filter by trip_id
        if ($request->has('trip_id')) {
            $query->forTrip($request->input('trip_id'));
        }

        // Filter by day_number
        if ($request->has('day_number')) {
            $query->forDay($request->input('day_number'));
        }

        // Order by day and time
        $query->ordered();

        $items = $query->paginate(15);

        return ItineraryItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItineraryItemRequest $request): ItineraryItemResource
    {
        $trip = Trip::findOrFail($request->input('trip_id'));
        
        // Check if user owns the trip
        $this->authorize('update', $trip);

        $item = ItineraryItem::create($request->validated());

        return new ItineraryItemResource($item->load(['trip', 'place']));
    }

    /**
     * Display the specified resource.
     */
    public function show(ItineraryItem $itineraryItem): ItineraryItemResource
    {
        $itineraryItem->load(['trip', 'place']);
        
        $this->authorize('view', $itineraryItem);

        return new ItineraryItemResource($itineraryItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItineraryItemRequest $request, ItineraryItem $itineraryItem): ItineraryItemResource
    {
        $itineraryItem->load('trip');
        
        $this->authorize('update', $itineraryItem);

        $itineraryItem->update($request->validated());

        return new ItineraryItemResource($itineraryItem->load(['trip', 'place']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItineraryItem $itineraryItem): JsonResponse
    {
        $itineraryItem->load('trip');
        
        $this->authorize('delete', $itineraryItem);

        $itineraryItem->delete();

        return response()->json(null, 204);
    }
}
