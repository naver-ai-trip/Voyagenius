<?php

namespace App\Http\Resources;

use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'favoritable_type' => match ($this->favoritable_type) {
                Place::class => 'place',
                Trip::class => 'trip',
                MapCheckpoint::class => 'map_checkpoint',
                default => $this->favoritable_type,
            },
            'favoritable_id' => $this->favoritable_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Include favoritable entity when loaded
            'favoritable' => $this->when($this->relationLoaded('favoritable'), function () {
                return match ($this->favoritable_type) {
                    Place::class => new PlaceResource($this->favoritable),
                    Trip::class => new TripResource($this->favoritable),
                    MapCheckpoint::class => new MapCheckpointResource($this->favoritable),
                    default => null,
                };
            }),
        ];
    }
}
