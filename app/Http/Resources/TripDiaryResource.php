<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripDiaryResource extends JsonResource
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
            'entry_date' => $this->entry_date->format('Y-m-d'),
            'text' => $this->text,
            'mood' => $this->mood,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'trip' => $this->whenLoaded('trip'),
            'user' => $this->whenLoaded('user'),
        ];
    }
}
