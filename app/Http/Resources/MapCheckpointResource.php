<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapCheckpointResource extends JsonResource
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
            'trip_id' => $this->trip_id,
            'user_id' => $this->user_id,
            'place_id' => $this->place_id,
            'title' => $this->title,
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Include place data when loaded
            'place' => $this->whenLoaded('place', function () {
                return new PlaceResource($this->place);
            }),
        ];
    }
}
