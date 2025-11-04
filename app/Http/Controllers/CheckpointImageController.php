<?php

namespace App\Http\Controllers;

use App\Models\MapCheckpoint;
use App\Models\CheckpointImage;
use App\Http\Requests\StoreCheckpointImageRequest;
use App\Http\Requests\UpdateCheckpointImageRequest;
use App\Http\Resources\CheckpointImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckpointImageController extends Controller
{
    /**
     * Display a listing of the checkpoint images.
     */
    public function index(MapCheckpoint $checkpoint): AnonymousResourceCollection
    {
        $images = CheckpointImage::query()
            ->forCheckpoint($checkpoint->id)
            ->recent()
            ->paginate(15);

        return CheckpointImageResource::collection($images);
    }

    /**
     * Store a newly uploaded checkpoint image.
     */
    public function store(StoreCheckpointImageRequest $request, MapCheckpoint $checkpoint): JsonResponse
    {
        // Authorization: Check if user can upload images to this checkpoint
        $this->authorize('create', [CheckpointImage::class, $checkpoint]);

        $validated = $request->validated();

        // Handle file upload
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        
        // Generate organized path: checkpoints/{trip_id}/{checkpoint_id}/{uuid}.{extension}
        $checkpoint->loadMissing('trip');
        $tripId = $checkpoint->trip_id;
        $checkpointId = $checkpoint->id;
        $uuid = Str::uuid();
        $path = "checkpoints/{$tripId}/{$checkpointId}/{$uuid}.{$extension}";
        
        // Store file on public disk
        Storage::disk('public')->put($path, file_get_contents($file));

        // Create database record
        $image = CheckpointImage::create([
            'map_checkpoint_id' => $checkpoint->id,
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'caption' => $validated['caption'] ?? null,
            'uploaded_at' => now(),
        ]);

        return (new CheckpointImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified checkpoint image.
     */
    public function show(MapCheckpoint $checkpoint, CheckpointImage $image): CheckpointImageResource
    {
        // Verify image belongs to this checkpoint
        if ($image->map_checkpoint_id !== $checkpoint->id) {
            abort(404);
        }

        $this->authorize('view', $image);

        // Eager load relationships for detailed view
        $image->load(['checkpoint', 'user']);

        return new CheckpointImageResource($image);
    }

    /**
     * Update the checkpoint image caption.
     */
    public function update(UpdateCheckpointImageRequest $request, MapCheckpoint $checkpoint, CheckpointImage $image): CheckpointImageResource
    {
        // Verify image belongs to this checkpoint
        if ($image->map_checkpoint_id !== $checkpoint->id) {
            abort(404);
        }

        $this->authorize('update', $image);

        $validated = $request->validated();
        $image->update($validated);

        return new CheckpointImageResource($image);
    }

    /**
     * Remove the checkpoint image and delete the file.
     */
    public function destroy(MapCheckpoint $checkpoint, CheckpointImage $image): JsonResponse
    {
        // Verify image belongs to this checkpoint
        if ($image->map_checkpoint_id !== $checkpoint->id) {
            abort(404);
        }

        $this->authorize('delete', $image);

        // Delete file from storage
        Storage::disk('public')->delete($image->file_path);

        // Delete database record
        $image->delete();

        return response()->json(null, 204);
    }
}
