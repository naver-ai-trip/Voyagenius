<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripDiaryRequest;
use App\Http\Requests\UpdateTripDiaryRequest;
use App\Http\Resources\TripDiaryResource;
use App\Models\Trip;
use App\Models\TripDiary;
use Illuminate\Http\Request;

class TripDiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TripDiary::query()
            ->where('user_id', auth()->id())
            ->with(['trip', 'user']);

        // Filter by trip_id
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->input('trip_id'));
        }

        // Filter by entry_date
        if ($request->has('entry_date')) {
            $query->whereDate('entry_date', $request->input('entry_date'));
        }

        // Filter by mood
        if ($request->has('mood')) {
            $query->where('mood', $request->input('mood'));
        }

        $diaries = $query->orderBy('entry_date', 'desc')
            ->paginate(15);

        return TripDiaryResource::collection($diaries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripDiaryRequest $request)
    {
        // Check if user owns the trip
        $trip = Trip::findOrFail($request->input('trip_id'));
        $this->authorize('view', $trip);

        $diary = TripDiary::create([
            'trip_id' => $request->input('trip_id'),
            'user_id' => auth()->id(),
            'entry_date' => $request->input('entry_date'),
            'text' => $request->input('text'),
            'mood' => $request->input('mood'),
        ]);

        $diary->load(['trip', 'user']);

        return new TripDiaryResource($diary);
    }

    /**
     * Display the specified resource.
     */
    public function show(TripDiary $diary)
    {
        $this->authorize('view', $diary);

        $diary->load(['trip', 'user']);

        return new TripDiaryResource($diary);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripDiaryRequest $request, TripDiary $diary)
    {
        $this->authorize('update', $diary);

        $diary->update($request->only(['text', 'mood']));

        $diary->load(['trip', 'user']);

        return new TripDiaryResource($diary);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripDiary $diary)
    {
        $this->authorize('delete', $diary);

        $diary->delete();

        return response()->noContent();
    }
}
