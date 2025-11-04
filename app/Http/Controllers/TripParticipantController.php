<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripParticipantRequest;
use App\Http\Requests\UpdateTripParticipantRequest;
use App\Http\Resources\TripParticipantResource;
use App\Models\Trip;
use App\Models\TripParticipant;
use Illuminate\Http\Request;

class TripParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Trip $trip)
    {
        $this->authorize('viewAny', [TripParticipant::class, $trip]);

        $query = $trip->participants()->with('user');

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $participants = $query->paginate(15);

        return TripParticipantResource::collection($participants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripParticipantRequest $request, Trip $trip)
    {
        $this->authorize('create', [TripParticipant::class, $trip]);

        $participant = TripParticipant::create([
            'trip_id' => $trip->id,
            'user_id' => $request->input('user_id'),
            'role' => $request->input('role'),
        ]);

        $participant->load('user');

        return new TripParticipantResource($participant);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip, TripParticipant $participant)
    {
        $this->authorize('view', $participant);

        $participant->load('user');

        return new TripParticipantResource($participant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripParticipantRequest $request, Trip $trip, TripParticipant $participant)
    {
        $this->authorize('update', $participant);

        $participant->update([
            'role' => $request->input('role'),
        ]);

        $participant->load('user');

        return new TripParticipantResource($participant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip, TripParticipant $participant)
    {
        $this->authorize('delete', $participant);

        $participant->delete();

        return response()->noContent();
    }
}
