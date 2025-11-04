<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Trip;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the user's favorites.
     */
    public function index(Request $request)
    {
        $query = Favorite::where('user_id', auth()->id())
            ->with('favoritable')
            ->latest();

        // Filter by favoritable_type
        if ($request->has('favoritable_type')) {
            $type = $request->input('favoritable_type');
            $modelClass = match ($type) {
                'place' => Place::class,
                'trip' => Trip::class,
                'map_checkpoint' => MapCheckpoint::class,
                default => null,
            };

            if ($modelClass) {
                $query->where('favoritable_type', $modelClass);
            }
        }

        $favorites = $query->paginate(15);

        return FavoriteResource::collection($favorites);
    }

    /**
     * Store a newly created favorite.
     */
    public function store(StoreFavoriteRequest $request)
    {
        $validated = $request->validated();

        // Convert simple type to model class
        $favoritableType = match ($validated['favoritable_type']) {
            'place' => Place::class,
            'trip' => Trip::class,
            'map_checkpoint' => MapCheckpoint::class,
        };

        $favorite = Favorite::create([
            'user_id' => auth()->id(),
            'favoritable_type' => $favoritableType,
            'favoritable_id' => $validated['favoritable_id'],
        ]);

        return new FavoriteResource($favorite);
    }

    /**
     * Display the specified favorite.
     */
    public function show(Favorite $favorite)
    {
        return new FavoriteResource($favorite->load('favoritable'));
    }

    /**
     * Remove the specified favorite.
     */
    public function destroy(Favorite $favorite)
    {
        $this->authorize('delete', $favorite);

        $favorite->delete();

        return response()->noContent();
    }
}
