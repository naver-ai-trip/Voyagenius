<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachTagRequest;
use App\Http\Requests\DetachTagRequest;
use App\Http\Resources\TagResource;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Tag;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    /**
     * List all tags with optional search and sort.
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort alphabetically by name
        if ($request->query('sort') === 'name') {
            $query->orderBy('name');
        }

        $tags = $query->paginate(15);

        return TagResource::collection($tags);
    }

    /**
     * List popular tags with usage_count >= min_count (default 10).
     */
    public function popular(Request $request)
    {
        $minCount = $request->integer('min_count', 10);

        $tags = Tag::popular($minCount)->paginate(15);

        return TagResource::collection($tags);
    }

    /**
     * Display a single tag.
     */
    public function show(Tag $tag)
    {
        return new TagResource($tag);
    }

    /**
     * Attach a tag to an entity (trip, place, or checkpoint).
     */
    public function attach(AttachTagRequest $request)
    {
        $tag = Tag::findOrFail($request->tag_id);

        // Determine the taggable entity
        $taggable = $this->resolveTaggable($request->taggable_type, $request->taggable_id);

        // Check if tag is already attached
        if ($taggable->tags()->where('tag_id', $tag->id)->exists()) {
            return response()->json([
                'message' => 'Tag is already attached to this entity'
            ], 422);
        }

        // Attach tag to entity
        $taggable->tags()->attach($tag->id);

        // Increment usage count
        $tag->incrementUsage();

        return response()->json([
            'message' => 'Tag attached successfully'
        ], 201);
    }

    /**
     * Detach a tag from an entity.
     */
    public function detach(DetachTagRequest $request)
    {
        $tag = Tag::findOrFail($request->tag_id);

        // Determine the taggable entity
        $taggable = $this->resolveTaggable($request->taggable_type, $request->taggable_id);

        // Detach tag from entity
        $taggable->tags()->detach($tag->id);

        // Decrement usage count (won't go below 0)
        $tag->decrementUsage();

        return response()->json([
            'message' => 'Tag detached successfully'
        ]);
    }

    /**
     * Resolve the taggable entity based on type and id.
     */
    private function resolveTaggable(string $type, int $id)
    {
        return match ($type) {
            'trip' => Trip::findOrFail($id),
            'place' => Place::findOrFail($id),
            'checkpoint' => MapCheckpoint::findOrFail($id),
            default => abort(422, 'Invalid taggable type'),
        };
    }
}
