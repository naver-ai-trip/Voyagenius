<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Convert full class name to simple type for API response
        $reviewableType = match ($this->reviewable_type) {
            'App\\Models\\Place' => 'place',
            'App\\Models\\MapCheckpoint' => 'map_checkpoint',
            default => $this->reviewable_type,
        };

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $this->reviewable_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Include reviewable (Place or MapCheckpoint) when loaded
            'reviewable' => $this->whenLoaded('reviewable', function () {
                if ($this->reviewable instanceof \App\Models\Place) {
                    return new PlaceResource($this->reviewable);
                } elseif ($this->reviewable instanceof \App\Models\MapCheckpoint) {
                    return new MapCheckpointResource($this->reviewable);
                }
                return null;
            }),
            
            // Include user when loaded
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
