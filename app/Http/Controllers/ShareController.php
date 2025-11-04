<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShareRequest;
use App\Http\Resources\ShareResource;
use App\Http\Resources\TripResource;
use App\Models\Share;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShareController extends Controller
{
    /**
     * Display a listing of shares for a trip.
     */
    public function index(Request $request, Trip $trip): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Share::class, $trip]);

        $query = Share::where('trip_id', $trip->id)
            ->with('user');

        // Filter by permission if provided
        if ($request->has('permission')) {
            $query->forPermission($request->input('permission'));
        }

        $shares = $query->paginate(15);

        return ShareResource::collection($shares);
    }

    /**
     * Store a newly created share in storage.
     */
    public function store(StoreShareRequest $request, Trip $trip): ShareResource
    {
        $this->authorize('create', [Share::class, $trip]);

        $share = Share::create([
            'trip_id' => $trip->id,
            'user_id' => auth()->id(),
            'permission' => $request->input('permission', 'viewer'),
        ]);

        return new ShareResource($share);
    }

    /**
     * Display the trip via share token.
     */
    public function show(string $token): TripResource
    {
        $share = Share::forToken($token)->firstOrFail();
        
        $trip = Trip::findOrFail($share->trip_id);

        return new TripResource($trip);
    }

    /**
     * Remove the specified share from storage.
     */
    public function destroy(Share $share): Response
    {
        $this->authorize('delete', $share);

        $share->delete();

        return response()->noContent();
    }
}
