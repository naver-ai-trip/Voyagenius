<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\UpdateChecklistItemRequest;
use App\Http\Resources\ChecklistItemResource;
use App\Models\ChecklistItem;
use App\Models\Trip;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ChecklistItem::query()
            ->where('user_id', auth()->id())
            ->with(['trip']);

        // Filter by trip_id
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->input('trip_id'));
        }

        // Filter by checked status
        if ($request->has('is_checked')) {
            $query->where('is_checked', $request->boolean('is_checked'));
        }

        $items = $query->latest()
            ->paginate(15);

        return ChecklistItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChecklistItemRequest $request)
    {
        // Check if user owns the trip
        $trip = Trip::findOrFail($request->input('trip_id'));
        $this->authorize('view', $trip);

        $item = ChecklistItem::create([
            'trip_id' => $request->input('trip_id'),
            'user_id' => auth()->id(),
            'content' => $request->input('content'),
            'is_checked' => false,
        ]);

        $item->load(['trip']);

        return new ChecklistItemResource($item);
    }

    /**
     * Display the specified resource.
     */
    public function show(ChecklistItem $checklistItem)
    {
        $this->authorize('view', $checklistItem);

        $checklistItem->load(['trip']);

        return new ChecklistItemResource($checklistItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChecklistItemRequest $request, ChecklistItem $checklistItem)
    {
        $this->authorize('update', $checklistItem);

        $checklistItem->update($request->only(['content', 'is_checked']));

        $checklistItem->load(['trip']);

        return new ChecklistItemResource($checklistItem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChecklistItem $checklistItem)
    {
        $this->authorize('delete', $checklistItem);

        $checklistItem->delete();

        return response()->noContent();
    }
}
