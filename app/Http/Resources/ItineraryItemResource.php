<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItineraryItemResource extends JsonResource
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
            'title' => $this->title,
            'day_number' => $this->day_number,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'place_id' => $this->place_id,
            'note' => $this->note,
            'duration_minutes' => $this->duration_minutes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'trip' => new TripResource($this->whenLoaded('trip')),
            'place' => new PlaceResource($this->whenLoaded('place')),
        ];
    }
}
