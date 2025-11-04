<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews.
     * Supports filtering by reviewable_type, reviewable_id, and rating.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Review::query();

        // Filter by reviewable_type
        if ($request->has('reviewable_type')) {
            $type = $request->input('reviewable_type');
            $modelClass = $type === 'place' ? Place::class : MapCheckpoint::class;
            $query->where('reviewable_type', $modelClass);
        }

        // Filter by reviewable_id
        if ($request->has('reviewable_id')) {
            $query->where('reviewable_id', $request->input('reviewable_id'));
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->rating($request->integer('rating'));
        }

        // Load relationships
        $query->with(['reviewable', 'user']);

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request): ReviewResource
    {
        // Convert simple type to full class name
        $reviewableType = $request->input('reviewable_type');
        $modelClass = $reviewableType === 'place' ? Place::class : MapCheckpoint::class;

        $review = Review::create([
            'user_id' => $request->user()->id,
            'reviewable_type' => $modelClass,
            'reviewable_id' => $request->input('reviewable_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        return new ReviewResource($review);
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): ReviewResource
    {
        // Load relationships
        $review->load(['reviewable', 'user']);
        
        return new ReviewResource($review);
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, Review $review): ReviewResource
    {
        $this->authorize('update', $review);

        $review->update($request->only([
            'rating',
            'comment',
        ]));

        return new ReviewResource($review);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review): Response
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->noContent();
    }
}
