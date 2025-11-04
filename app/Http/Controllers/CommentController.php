<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'entity']);

        // Filter by entity_type
        if ($request->has('entity_type')) {
            $entityClass = match ($request->input('entity_type')) {
                'trip' => \App\Models\Trip::class,
                'map_checkpoint' => \App\Models\MapCheckpoint::class,
                'trip_diary' => \App\Models\TripDiary::class,
                default => null,
            };

            if ($entityClass) {
                $query->where('entity_type', $entityClass);
            }
        }

        // Filter by entity_id
        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        $comments = $query->paginate(15);

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request)
    {
        $comment = Comment::create([
            'user_id' => auth()->id(),
            'entity_type' => $request->input('entity_class'),
            'entity_id' => $request->input('entity_id'),
            'content' => $request->input('content'),
        ]);

        $comment->load(['user', 'entity']);

        return new CommentResource($comment);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        $comment->load(['user', 'entity']);

        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $comment->update([
            'content' => $request->input('content'),
        ]);

        $comment->load(['user', 'entity']);

        return new CommentResource($comment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
